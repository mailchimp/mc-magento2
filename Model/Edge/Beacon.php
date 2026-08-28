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
     * @var bool the cron slot has been settled for this process
     */
    private $slotClaimed = false;

    /**
     * @var bool a config value was written and the cache still shows the old one
     */
    private $configDirty = false;

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
        try {
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = (int)$store->getId();

                if (!$this->helper->isMailChimpEnabled($storeId) || !$this->helper->getApiKey($storeId)) {
                    continue;
                }

                try {
                    $this->processStore($storeId, (string)$store->getBaseUrl());
                } catch (\Exception $e) {
                    $this->helper->log('Edge beacon failed for store ' . $storeId . ': ' . $e->getMessage());
                } catch (\Throwable $t) {
                    // On PHP 7+ an Error is not an Exception, so catching only
                    // Exception here let a TypeError out of the API path abort
                    // the run and take every remaining store view with it.
                    // One store view's problem is not the other views'.
                    $this->helper->log('Edge beacon failed for store ' . $storeId . ': ' . $t->getMessage());
                }
            }
        } finally {
            // One refresh for the whole run rather than one per value written.
            // A flush re-reads the entire config and then serialises and
            // encrypts every scope under a lock, so its cost grows with the
            // number of store views; flushing per write would make a token
            // expiry across N views pay that N times over. Nothing above reads
            // back what it wrote, so deferring to here changes no behaviour.
            //
            // In `finally` because the deferred writes are already in the
            // database. Anything that leaves this method without flushing —
            // and something reaching here uncaught is exactly that case —
            // would leave a cleared token stored while the config cache keeps
            // serving the old one, until something unrelated happens to flush.
            if ($this->configDirty) {
                $this->helper->flushConfigCache();
                $this->configDirty = false;
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

        $this->helper->saveConfigValueWithoutCacheFlush(
            MailChimpHelper::XML_EDGE_TOKEN,
            $token,
            $storeId,
            ScopeInterface::SCOPE_STORES
        );
        $this->configDirty = true;

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
                // Top level, not nested: the receiving side reads these as
                // first-class keys and drops anything it does not recognise, so
                // an `account` wrapper would be accepted with a 200 and thrown
                // away. Same shape the register already sends.
                $body = array_merge($body, $account);
            }
        }

        $response = $this->client->ping($token, $body);

        if ($response->isUnauthorized()) {
            // The only condition that clears the token. Next tick re-registers.
            $this->helper->saveConfigValueWithoutCacheFlush(
                MailChimpHelper::XML_EDGE_TOKEN,
                '',
                $storeId,
                ScopeInterface::SCOPE_STORES
            );
            $this->configDirty = true;
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
            $this->helper->saveConfigValueWithoutCacheFlush(
                MailChimpHelper::XML_EDGE_DELIVERY_UID,
                '',
                $storeId,
                ScopeInterface::SCOPE_STORES
            );
            $this->configDirty = true;
        }

        // The acks just sent are gone as far as the service is concerned, so
        // nothing is still pending. Passed in rather than re-read: see
        // NotificationDelivery::rememberForAck.
        $this->notifications->handle($storeId, $token, $response->getData(), []);
    }

    /**
     * Whether the account owner's name and address may travel with a report.
     *
     * Only an explicit no counts as a refusal. An absent value means the
     * question has never been answered on this installation — which is what an
     * upgrade from a version that predates the switch looks like — and that is
     * not the same as declining. config.xml supplies the default, so in
     * practice the absent case only arises if configuration cannot be read at
     * all, and failing open there matches how the API library reads the very
     * same path.
     *
     * @param  int $storeId
     * @return bool
     */
    private function contactAllowed($storeId)
    {
        $value = $this->helper->getConfigValue(MailChimpHelper::XML_TELEMETRY_SHARE_CONTACT, $storeId);

        if ($value === null || $value === '') {
            return true;
        }

        return (bool)$value;
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
        } catch (\Throwable $t) {
            // Reading the account must not be able to end the run: an Error
            // here is a broken API path, not a reason to stop reporting.
            $this->helper->log('Edge beacon could not read the Mailchimp account: ' . $t->getMessage());
            return null;
        }

        if (!is_array($root)) {
            return null;
        }

        $fields = ['account_id', 'pricing_plan_type', 'total_subscribers'];

        // Everything that names or reaches the person behind the account is
        // sent only while the merchant allows it. The admin switch says it
        // leaves the owner's name and address out of these reports, and a
        // switch that is not obeyed is worse than no switch at all.
        //
        // account_name is in that list even though it is the account's name
        // rather than a field about a person. Accounts opened by a business
        // carry a company there, but accounts opened by an individual commonly
        // carry their name — and a field that may or may not name someone has
        // to be treated as though it does once they have asked us not to. The
        // API library reads the same switch and withholds the same value, so
        // gating it here is also what keeps one switch from meaning two things.
        //
        // account_id still travels, so reports remain countable and joinable
        // with the account opted out. What is lost is a human-readable name on
        // a dashboard, which is precisely what was declined.
        if ($this->contactAllowed($storeId)) {
            $fields = array_merge(
                $fields,
                ['account_name', 'email', 'first_name', 'last_name', 'username']
            );
        }

        $block = [];
        foreach ($fields as $field) {
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
        // Sign-masked: crc32() returns a NEGATIVE int on 32-bit PHP builds, and a
        // negative slot can never equal an hour, so those installs would never
        // refresh their account data. Silent, and 32-bit builds still exist on
        // older shared hosting.
        $slot = (crc32($storeId . $storeUrl) & 0x7fffffff) % self::ACCOUNT_BLOCK_PERIOD_HOURS;
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
        // The read below cannot be trusted to see a write made earlier in this
        // same process: within one request the config a scope has already
        // loaded is refreshed only as a side effect of the cache-warming path,
        // which is skipped when the config cache type is disabled. On a fresh
        // multi-store-view install every view registers on the first tick, each
        // read returns empty, and each writes a different minute — the last one
        // winning, after N full config-cache flushes. This flag makes the claim
        // once per process regardless.
        if ($this->slotClaimed) {
            return;
        }

        $existing = $this->scopeConfig->getValue(MailChimpHelper::XML_EDGE_BEACON_CRON);
        if ($existing) {
            $this->slotClaimed = true;
            return;
        }

        // Concatenation, not addition: on PHP 8 "abc" + "def" is a TypeError,
        // and for all-numeric ids it would silently produce a different slot.
        // Sign-masked for the same reason as the slot above: a negative minute
        // writes an invalid cron expression, and the job then never runs at all.
        $minute = (crc32($accountId . $storeUrl) & 0x7fffffff) % 60;

        $this->helper->saveConfigValueWithoutCacheFlush(
            MailChimpHelper::XML_EDGE_BEACON_CRON,
            $minute . ' * * * *',
            0,
            'default'
        );

        $this->slotClaimed = true;
        $this->configDirty = true;
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
        } catch (\Throwable $t) {
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
