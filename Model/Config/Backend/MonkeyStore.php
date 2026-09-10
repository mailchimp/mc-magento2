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
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helper          = $helper;
        $this->syncHelper       = $syncHelper;
        $this->resourceConfig   = $resourceConfig;
        $this->_date            = $date;
        $this->_storeManager    = $storeManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $data = $this->getData('groups');
        $found = 0;
        $newListId = null;
        if (isset($data['ecommerce']['fields']['active']['value'])) {
            $active = $data['ecommerce']['fields']['active']['value'];
        } elseif ($data['ecommerce']['fields']['active']['inherit']) {
            $active = $data['ecommerce']['fields']['active']['inherit'];
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
