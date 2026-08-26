<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Hourly status report to the ebizmarts service: register once, then ping forever.
 *
 * Additive by design. Nothing here touches the existing registration to
 * users-mc4mage, the hourly batch push or enable_support.
 */
class Beacon
{
    /**
     * The account block rides two pings a day, twelve hours apart. The slot is
     * derived from the store identity rather than persisted, so there is no
     * "last refresh" timestamp to write on every tick.
     */
    const ACCOUNT_BLOCK_PERIOD_HOURS = 12;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LivenessSignals
     */
    private $signals;

    /**
     * @var NotificationDelivery
     */
    private $notifications;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param StoreManagerInterface    $storeManager
     * @param MailChimpHelper          $helper
     * @param Client                   $client
     * @param LivenessSignals          $signals
     * @param NotificationDelivery     $notifications
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface     $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        MailChimpHelper $helper,
        Client $client,
        LivenessSignals $signals,
        NotificationDelivery $notifications,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager    = $storeManager;
        $this->helper          = $helper;
        $this->client          = $client;
        $this->signals         = $signals;
        $this->notifications   = $notifications;
        $this->productMetadata = $productMetadata;
        $this->scopeConfig     = $scopeConfig;
    }

    /**
     * One tick: every store view that has an API key and an active extension.
     *
     * A failure on one store view never aborts the loop.
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();

            if (!$this->helper->isMailChimpEnabled($storeId) || !$this->helper->getApiKey($storeId)) {
                continue;
            }

            try {
                $this->processStore($storeId, (string)$store->getBaseUrl());
            } catch (\Exception $e) {
                $this->helper->log('Edge beacon failed for store ' . $storeId . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Register when there is no token, then ping regardless.
     *
     * @param  int    $storeId
     * @param  string $storeUrl
     * @return void
     */
    private function processStore($storeId, $storeUrl)
    {
        $token = (string)$this->helper->getConfigValue(MailChimpHelper::XML_EDGE_TOKEN, $storeId);

        if ($token === '') {
            $token = $this->register($storeId, $storeUrl);
            if ($token === null) {
                // Nothing written. The remote upsert is idempotent, so the next
                // hourly tick simply tries again.
                return;
            }
        }

        $this->ping($storeId, $storeUrl, $token);
    }

    /**
     * Register the store view and store the token it returns.
     *
     * The account fields cost one GET / against Mailchimp, and only here.
     *
     * @param  int    $storeId
     * @param  string $storeUrl
     * @return string|null
     */
    private function register($storeId, $storeUrl)
    {
        $account = $this->accountBlock($storeId);
        if ($account === null) {
            // Without the account we have no identity to register under.
            return null;
        }

        $body = array_merge(
            ['store_url' => $storeUrl],
            $account,
            $this->versionBlock()
        );

        $response = $this->client->register($body);
        if (!$response->isOk()) {
            return null;
        }

        $data  = $response->getData();
        $token = isset($data['token']) ? (string)$data['token'] : '';
        if ($token === '') {
            $this->helper->log('Edge beacon registered store ' . $storeId . ' but no token came back');
            return null;
        }

        $this->helper->saveConfigValue(
            MailChimpHelper::XML_EDGE_TOKEN,
            $token,
            $storeId,
            ScopeInterface::SCOPE_STORES
        );

        $this->claimBeaconSlot(isset($account['account_id']) ? (string)$account['account_id'] : '', $storeUrl);

        return $token;
    }

    /**
     * Send the status report and act on the reply.
     *
     * @param  int    $storeId
     * @param  string $storeUrl
     * @param  string $token
     * @return void
     */
    private function ping($storeId, $storeUrl, $token)
    {
        $body = array_merge(
            ['store_url' => $storeUrl],
            $this->versionBlock(),
            $this->signals->forStore($storeId)
        );

        // Two-step ack, opportunistic: the service acknowledges each uid
        // included in the ping. `read[]` is deliberately
        // never sent - we can prove a message reached the inbox, not that the
        // merchant opened it.
        $pendingAcks = $this->pendingAcks($storeId);
        if (!empty($pendingAcks)) {
            $body['received'] = $pendingAcks;
        }

        if ($this->shouldSendAccountBlock($storeId, $storeUrl)) {
            // Deliberately outside any try that wraps the ping itself: a
            // Mailchimp outage must not make every installation look dead.
            $account = $this->accountBlock($storeId);
            if ($account !== null) {
                $body['account'] = $account;
            }
        }

        $response = $this->client->ping($token, $body);

        if ($response->isUnauthorized()) {
            // The only condition that clears the token. Next tick re-registers.
            $this->helper->saveConfigValue(
                MailChimpHelper::XML_EDGE_TOKEN,
                '',
                $storeId,
                ScopeInterface::SCOPE_STORES
            );
            return;
        }

        if ($response->isRateLimited() || !$response->isOk()) {
            // Token untouched, and the acks stay queued: a ping that never
            // landed has not confirmed anything.
            return;
        }

        // Cleared only once the service has actually seen them, and before the
        // pull below can queue a fresh batch.
        if (!empty($pendingAcks)) {
            $this->helper->saveConfigValue(
                MailChimpHelper::XML_EDGE_DELIVERY_UID,
                '',
                $storeId,
                ScopeInterface::SCOPE_STORES
            );
        }

        $this->notifications->handle($storeId, $token, $response->getData());
    }

    /**
     * Delivery uids waiting to be acknowledged, queued by the last pull.
     *
     * @param  int $storeId
     * @return array
     */
    private function pendingAcks($storeId)
    {
        $pending = (string)$this->helper->getConfigValue(MailChimpHelper::XML_EDGE_DELIVERY_UID, $storeId);
        if ($pending === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $pending)));
    }

    /**
     * The Mailchimp account block, or null when the call fails.
     *
     * @param  int $storeId
     * @return array|null
     */
    private function accountBlock($storeId)
    {
        try {
            $api  = $this->helper->getApi($storeId);
            $root = $api->root->info();
        } catch (\Exception $e) {
            $this->helper->log('Edge beacon could not read the Mailchimp account: ' . $e->getMessage());
            return null;
        }

        if (!is_array($root)) {
            return null;
        }

        $block = [];
        foreach (
            [
                'account_id', 'account_name', 'email', 'first_name',
                'last_name', 'username', 'pricing_plan_type', 'total_subscribers',
            ] as $field
        ) {
            if (isset($root[$field])) {
                $block[$field] = $root[$field];
            }
        }

        return $block;
    }

    /**
     * Whether this tick is one of the store's two daily account slots.
     *
     * Derived from the store identity, so it needs no persisted state and a
     * given store always refreshes at the same two hours.
     *
     * @param  int    $storeId
     * @param  string $storeUrl
     * @return bool
     */
    private function shouldSendAccountBlock($storeId, $storeUrl)
    {
        $slot = crc32($storeId . $storeUrl) % self::ACCOUNT_BLOCK_PERIOD_HOURS;
        $hour = (int)$this->helper->getGmtDate('G');

        return $hour % self::ACCOUNT_BLOCK_PERIOD_HOURS === $slot;
    }

    /**
     * Write the install's beacon minute, once.
     *
     * The stagger is per installation: one install, one slot, one write. The first
     * store view to register successfully claims it and later ones leave it
     * alone, so the value never flaps.
     *
     * It has to live in DEFAULT scope: Magento's scheduler reads config_path
     * with no store id (ProcessCronQueueObserver::getConfigSchedule), so a
     * value written per store view would never be seen and the job would sit
     * on its bootstrap minute forever, looking configured.
     *
     * @param  string $accountId
     * @param  string $storeUrl
     * @return void
     */
    private function claimBeaconSlot($accountId, $storeUrl)
    {
        $existing = $this->scopeConfig->getValue(MailChimpHelper::XML_EDGE_BEACON_CRON);
        if ($existing) {
            return;
        }

        // Concatenation, not addition: on PHP 8 "abc" + "def" is a TypeError,
        // and for all-numeric ids it would silently produce a different slot.
        $minute = crc32($accountId . $storeUrl) % 60;

        $this->helper->saveConfigValue(
            MailChimpHelper::XML_EDGE_BEACON_CRON,
            $minute . ' * * * *',
            0,
            'default'
        );
    }

    /**
     * Version facts, on both register and ping.
     *
     * @return array
     */
    private function versionBlock()
    {
        try {
            $moduleVersion = (string)$this->helper->getModuleVersion();
        } catch (\Exception $e) {
            $moduleVersion = '';
        }

        // The key names are the service's, not ours: it projects the body onto a
        // closed whitelist and silently drops anything outside it. `module_version`
        // and `magento_version` are also the two it promotes to their own columns
        // and writes on EVERY ping, so a merchant upgrading mid-day shows up on the
        // next beacon rather than waiting for a profile change.
        return [
            'module_version'  => $moduleVersion,
            'magento_version' => (string)$this->productMetadata->getVersion(),
            'php_version'     => PHP_VERSION,
        ];
    }
}
