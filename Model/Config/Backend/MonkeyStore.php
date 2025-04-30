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
                $this->_helper->saveConfigValue(
                    Data::XML_PATH_LIST,
                    $newListId,
                    $this->getScopeId(),
                    $this->getScope()
                );
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
            if ($found==1) {
                $this->_helper->cancelAllPendingBatches($mailchimpStore);
                $this->syncHelper->resetErrors($mailchimpStore, $this->getScopeId(), true);
            }
            $this->_helper->restoreAllCanceledBatches($this->getValue());
            if ($createWebhook) {
                $this->_helper->createWebHook($apiKey, $newListId);
            }
        }
        return parent::beforeSave();
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
