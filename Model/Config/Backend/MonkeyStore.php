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
use Ebizmarts\MailChimp\Model\Service\Pixel\NewConnectionQueue;
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
     * @var NewConnectionQueue
     */
    private NewConnectionQueue $newConnectionQueue;

    /**
     * @var ConfigDataCollectionFactory
     */
    private ConfigDataCollectionFactory $configDataCollectionFactory;

    /**
     * Store views this save may connect: every store view the saved scope
     * covers that was not connected before it.
     *
     * Read in beforeSave(), where the old configuration is still what the scope
     * config answers with, and acted on in afterSave(), once the new value is
     * persisted. Some of these will not end up connected -- a store view with
     * its own store or its own active flag is not changed by a wider save --
     * and ConfigObserver drops those once the configuration is re-initialised.
     *
     * @var int[]
     */
    private array $notYetConnectedStoreIds = [];
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
        NewConnectionQueue $newConnectionQueue,
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
        $this->newConnectionQueue = $newConnectionQueue;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        // Which store views this save may connect. Read first, before anything
        // below writes config: saveConfigValue() flushes the config cache, and
        // after that the scope config can answer with values this same save has
        // already written, which would make a store view this save connects
        // look as if it had been connected all along.
        $this->notYetConnectedStoreIds = $this->isARealStore($this->getValue())
            ? $this->storeViewsNotYetConnected()
            : [];

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
        return parent::beforeSave();
    }

    /**
     * Queue the Pixel for the store views this save may have connected.
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
     * A connection made at default or website scope connects every store view
     * under it that inherits it, so those are queued too. That is how a
     * single-store install always connects (the store field is visible at
     * default scope, so single-store mode saves it there), and how most
     * installs with one store view are configured. The Pixel itself is still
     * provisioned and stored per store view.
     *
     * This only decides; it does not call Mailchimp. afterSave() runs inside
     * Config::save()'s transaction, before Magento re-initialises the
     * configuration, so the provisioner's reads through the scope config would
     * still see the values from before this save. On a first connection that
     * includes the API key, which the OAuth wizard puts in the same Save Config
     * as the store. The store views are queued instead, and
     * Observer\ConfigObserver provisions the ones that did end up connected,
     * once the save is committed and the configuration re-initialised (see
     * Model/Service/Pixel/NewConnectionQueue).
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        $storeIds = $this->notYetConnectedStoreIds;
        $this->notYetConnectedStoreIds = [];

        if (!$storeIds) {
            return $this;
        }

        $decided = $this->storeViewsWithAPixelDecision($storeIds);

        foreach ($storeIds as $storeId) {
            if (!isset($decided[$storeId])) {
                $this->newConnectionQueue->add($storeId);
            }
        }

        return $this;
    }

    /**
     * The store views covered by the scope being saved that are not connected
     * yet, read from the configuration as it was before this save.
     *
     * Default covers every store view, a website covers its own, and a store
     * view covers itself. A covered store view may still not be connected by
     * this save -- it can hold its own store or its own active flag -- which
     * ConfigObserver checks against the committed configuration.
     *
     * @return int[]
     */
    private function storeViewsNotYetConnected(): array
    {
        $scope = $this->getScope();
        $scopeId = (int)$this->getScopeId();

        if ($scope === 'stores') {
            $covered = [$scopeId];
        } else {
            $covered = [];
            foreach ($this->_storeManager->getStores() as $storeId => $store) {
                if ($scope === 'websites' && (int)$store->getWebsiteId() !== $scopeId) {
                    continue;
                }
                $covered[] = (int)$storeId;
            }
        }

        return array_values(array_filter($covered, function ($storeId) {
            return !$this->_helper->isStoreConnected($storeId);
        }));
    }

    /**
     * The store views among these that already have a Pixel value someone set.
     *
     * The question is whether a ROW applies to the store view, not what it
     * resolves to: every store view resolves to the `0` in config.xml, so
     * reading the value would answer "decided, and the answer is No" for a
     * merchant who has never been asked. The toggle is locked until the view is
     * connected and an untouched save writes nothing
     * (Model/Config/Backend/PixelEnabled), so a row was written by someone
     * choosing.
     *
     * A row counts at the store view itself, at its website or at default. The
     * field is store-view only, but single-store mode saves it at website scope
     * (Magento\Config\Model\Config::_checkSingleStoreMode()), and a value set
     * by `config:set` at default applies to every store view. Each is a choice
     * that reaches the store view, so none is overridden.
     *
     * One query for all of them: a default-scope connection can cover many.
     *
     * @param  int[] $storeIds
     * @return true[]  keyed by store view id
     */
    private function storeViewsWithAPixelDecision(array $storeIds): array
    {
        $rows = $this->configDataCollectionFactory->create()
            ->addFieldToFilter('path', Data::XML_PIXEL_ENABLED_FOR_STORE)
            ->getData();

        $stores = [];
        $websites = [];
        foreach ($rows as $row) {
            if ($row['scope'] === 'default') {
                return array_fill_keys($storeIds, true);
            }
            if ($row['scope'] === 'websites') {
                $websites[(int)$row['scope_id']] = true;
            } elseif ($row['scope'] === 'stores') {
                $stores[(int)$row['scope_id']] = true;
            }
        }

        $decided = [];
        foreach ($storeIds as $storeId) {
            if (isset($stores[$storeId])) {
                $decided[$storeId] = true;
                continue;
            }
            if ($websites) {
                $websiteId = (int)$this->_storeManager->getStore($storeId)->getWebsiteId();
                if (isset($websites[$websiteId])) {
                    $decided[$storeId] = true;
                }
            }
        }

        return $decided;
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
