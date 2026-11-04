<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/29/17 4:29 PM
 * @file: MonkeyStore.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Backend;

use Ebizmarts\MailChimp\Helper\Data;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManager;
use Mailchimp_Error;
use Mailchimp_HttpError;

class MonkeyStore extends Value
{
    private Data $_helper;
    private SyncHelper $syncHelper;
    protected Config $resourceConfig;
    private DateTime $_date;
    private StoreManager $_storeManager;

    private $oldListId = null;

    /**
     * @var ConnectedSiteProvisioner
     */
    private ConnectedSiteProvisioner $connectedSiteProvisioner;

    /**
     * @var ConfigDataCollectionFactory
     */
    private ConfigDataCollectionFactory $configDataCollectionFactory;

    /**
     * Whether this save is the one that connects the store view.
     *
     * Read in beforeSave(), where the old value is still readable, and acted on
     * in afterSave(), once the new one is persisted and the provisioner's own
     * reads agree with it.
     *
     * @var bool
     */
    private bool $justConnected = false;
    const MAX_LISTS = 200;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Config $resourceConfig,
        DateTime $date,
        Data $helper,
        SyncHelper $syncHelper,
        StoreManager $storeManager,
        ConnectedSiteProvisioner $connectedSiteProvisioner,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helper          = $helper;
        $this->syncHelper       = $syncHelper;
        $this->resourceConfig   = $resourceConfig;
        $this->_date            = $date;
        $this->_storeManager    = $storeManager;
        $this->connectedSiteProvisioner = $connectedSiteProvisioner;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $data = $this->getData('groups');
        $found = 0;
        $newListId = null;
        if (isset($data['ecommerce']['fields']['active']['value'])) {
            $active = $data['ecommerce']['fields']['active']['value'];
        } elseif (!empty($data['ecommerce']['fields']['active']['inherit'])) {
            // 'inherit' is a flag, not a value: Magento posts inherit => 1 with
            // no 'value' whenever "Use Default" is ticked, whatever the
            // inherited setting happens to be. Reading the flag made $active
            // true for every inheriting scope, so a scope inheriting ecommerce
            // OFF would be told to turn ecommerce off -- with no way to comply
            // at that scope, since the field it would have to change is the one
            // it is inheriting. Resolve what is actually inherited instead.
            $active = $this->_helper->getConfigValue(
                Data::XML_PATH_ECOMMERCE_ACTIVE,
                $this->getScopeId(),
                $this->getScope()
            );
        } else {
            $active = 0;
        }
        // The dropdown offers two values that are not stores: -1, its
        // placeholder (Model/Config/Source/MonkeyStore.php), and 0, the
        // '---No Data---' option it falls back to when there is no API key at
        // this scope or the store listing threw. Persisting either leaves a
        // setting that is not a store, which every later reader has to know to
        // special-case, and three already do.
        //
        // Only while ecommerce is active. With it off, "no store selected" is a
        // legitimate state, and rejecting it here would stop a merchant saving
        // any Mailchimp configuration at all -- including the switch that would
        // turn ecommerce off.
        //
        // Above the isValueChanged() gate on purpose, so this fires on every
        // save of the section rather than only when the dropdown was touched.
        // An install already holding -1 meets it the next time it saves
        // anything here, which is what takes the value out of circulation
        // rather than preserving it until someone happens to reopen that
        // field.
        if ($active && !$this->isARealStore($this->getValue())) {
            throw new LocalizedException(
                __('Select a Mailchimp store, or turn Mailchimp ecommerce off. '
                    . 'Ecommerce cannot sync without one.')
            );
        }

        if ($active && $this->isValueChanged()) {
            $mailchimpStore     = $this->getOldValue();
            // charge the $newListId
            if (isset($data['general']['fields']['apikey']['value'])) {
                $apiKey = $data['general']['fields']['apikey']['value'];
            } else {
                $apiKey = $this->_helper->getApiKey($this->getScopeId(), $this->getScope());
            }
            if (isset($data['general']['fields']['monkeylist']['value'])) {
                $newListId = $data['general']['fields']['monkeylist']['value'];
            } else {
                $newListId = $this->getStore($apiKey, $this->getValue());
                // getStore() answers null when its own lookup fails -- a 404 on
                // a store Mailchimp no longer has, a revoked key. Writing that
                // replaced a working audience id with nothing, so a failed
                // lookup did not merely fail: it destroyed the answer it could
                // not confirm. Keep what is there and let the failure be a
                // failure.
                if ($newListId) {
                    $this->_helper->saveConfigValue(
                        Data::XML_PATH_LIST,
                        $newListId,
                        $this->getScopeId(),
                        $this->getScope()
                    );
                }
            }
            $this->oldListId = $this->_helper->getConfigValue(
                Data::XML_PATH_LIST,
                $this->getScopeId(),
                $this->getScope()
            );

            $createWebhook = true;
            $this->_helper->deleteConfig(
                Data::XML_MAILCHIMP_JS_URL,
                $this->getScopeId(),
                $this->getScope()
            );
            foreach ($this->_storeManager->getStores() as $storeId => $val) {
                $mstoreId = $this->_helper->getConfigValue(
                    Data::XML_MAILCHIMP_STORE,
                    $storeId
                );
                if ($mstoreId == $mailchimpStore) {
                    $this->_helper->deleteConfig(
                        Data::XML_MAILCHIMP_JS_URL,
                        $storeId,
                        'stores'
                    );
                    $found++;
                }
                $listId = $this->_helper->getConfigValue(Data::XML_PATH_LIST, $storeId);
                if ($listId == $newListId) {
                    $createWebhook = false;
                }
            }
            // The URLs above are gone, but a store view whose lookup had been
            // failing also holds a marker saying not to ask again for a while.
            // Leaving it would make this save appear not to have worked until
            // the marker expired, which is the one thing an admin changing the
            // store is entitled to see take effect now.
            $this->_helper->clearJsUrlFailures();
            if ($found==1) {
                $this->_helper->cancelAllPendingBatches($mailchimpStore);
                $this->syncHelper->resetErrors($mailchimpStore, $this->getScopeId(), true);
            }
            $this->_helper->restoreAllCanceledBatches($this->getValue());
            // Same null, one consequence further on: without this the failed
            // lookup went on to register a webhook against no audience at all.
            if ($createWebhook && $newListId) {
                $this->_helper->createWebHook($apiKey, $newListId);
            }
        }
        // Whether this save is the one that connects the store view. Read here
        // because the stored value is still the old one; acted on in
        // afterSave().
        $this->justConnected = $this->isARealStore($this->getValue())
            && !$this->_helper->isStoreConnected($this->resolveStoreIdForScope());

        return parent::beforeSave();
    }

    /**
     * Turn the Pixel on for a store view that just connected.
     *
     * Mailchimp asks for the Pixel to be active on every new connection, so the
     * connection itself provisions it rather than waiting for the merchant to
     * find the toggle. Three rules shape what this does and does not do:
     *
     *   - only on a connection. A store view that was already connected is left
     *     exactly as it is, including its Pixel setting, so an upgrade changes
     *     nothing for anyone;
     *   - an explicit No is never overridden. The toggle is locked until the
     *     view is connected and an untouched save writes no row, so a value
     *     found here was chosen by someone;
     *   - the bound connected site only. A standalone site cannot attribute to
     *     the store, so on a host that already has one the Pixel stays off and
     *     the reason is logged.
     *
     * A provisioning failure never fails the connection: it is logged and the
     * merchant can still turn the Pixel on by hand, which provisions again.
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        if (!$this->justConnected) {
            return $this;
        }

        $this->justConnected = false;
        $storeId = $this->resolveStoreIdForScope();

        if ($storeId === null) {
            // The connection was made at default or website scope, where the
            // Pixel -- a store-view-only setting -- has no row to write.
            return $this;
        }

        if ($this->pixelAlreadyDecided($storeId)) {
            return $this;
        }

        try {
            $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();
            $this->connectedSiteProvisioner->provision(
                $storeId,
                (string)$this->getValue(),
                (string)parse_url($baseUrl, PHP_URL_HOST),
                true
            );
        } catch (\Throwable $e) {
            // Deliberately \Throwable and deliberately swallowed. This runs
            // inside the save of the field that connects the store, and a
            // Mailchimp outage, a revoked key or an error anywhere in the
            // provisioner must not leave the merchant unable to connect.
            $this->_helper->log(
                'MonkeyStore: could not provision the Pixel for store ' . $storeId
                . ': ' . $e->getMessage()
            );
        }

        return $this;
    }

    /**
     * Whether someone has already set the Pixel toggle for this store view.
     *
     * The question is whether a ROW exists at that scope, not what the scope
     * resolves to: every store view resolves to the `0` in config.xml, so
     * reading the value would answer "decided, and the answer is No" for a
     * merchant who has never been asked. The toggle is locked until the view is
     * connected and an untouched save writes nothing
     * (Model/Config/Backend/PixelEnabled), so a row here was written by someone
     * choosing.
     *
     * @param  int $storeId
     * @return bool
     */
    private function pixelAlreadyDecided(int $storeId): bool
    {
        $rows = $this->configDataCollectionFactory->create()
            ->addFieldToFilter('path', Data::XML_PIXEL_ENABLED_FOR_STORE)
            ->addFieldToFilter('scope', 'stores')
            ->addFieldToFilter('scope_id', $storeId);

        return $rows->getSize() > 0;
    }

    /**
     * The store view this save applies to, or null when it is wider than one.
     *
     * @return int|null
     */
    private function resolveStoreIdForScope()
    {
        return $this->getScope() === 'stores' ? (int)$this->getScopeId() : null;
    }

    /**
     * Whether a submitted value is a Mailchimp store rather than one of the
     * dropdown's two non-answers.
     *
     * Falsy covers 0, '0' and the empty string; -1 is compared loosely because
     * the form submits it as a string. This mirrors the guard the read side
     * already applies in Helper\Data::getJsUrl().
     *
     * @param  mixed $value
     * @return bool
     */
    private function isARealStore($value)
    {
        return (bool)$value && $value != -1;
    }

    private function getStore($apiKey, $store)
    {
        try {
            $api = $this->_helper->getApiByApiKey($apiKey);
            $store = $api->ecommerce->stores->get($store);
            return $store['list_id'];
        } catch (Mailchimp_Error | Mailchimp_HttpError $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }
        return null;
    }
}
