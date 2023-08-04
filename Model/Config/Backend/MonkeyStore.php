<?php

namespace Ebizmarts\MailChimp\Model\Config\Backend;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class MonkeyStore extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $_date;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $_storeManager;

    private $oldListId = null;
    const MAX_LISTS = 200;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        \Magento\Store\Model\StoreManager $storeManager,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->syncHelper = $syncHelper;
        $this->resourceConfig = $resourceConfig;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
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
            $mailchimpStore = $this->getOldValue();
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
                    \Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST,
                    $newListId,
                    $this->getScopeId(),
                    $this->getScope()
                );
            }
            $this->oldListId = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST,
                $this->getScopeId(),
                $this->getScope()
            );

            $createWebhook = true;
            $this->_helper->deleteConfig(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL,
                $this->getScopeId(),
                $this->getScope()
            );
            foreach ($this->_storeManager->getStores() as $storeId => $val) {
                $mstoreId = $this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                    $storeId
                );
                if ($mstoreId == $mailchimpStore) {
                    $this->_helper->deleteConfig(
                        \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL,
                        $storeId,
                        'stores'
                    );
                    $found++;
                }
                $listId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId);
                if ($listId == $newListId) {
                    $createWebhook = false;
                }
            }
            if ($found == 1) {
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
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }

        return null;
    }
}
