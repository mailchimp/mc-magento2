<?php

namespace Ebizmarts\MailChimp\Cron;

use Magento\Store\Model\StoreManager;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification\CollectionFactory as MailchimpNotificationCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;

class SyncStatistics
{
    /**
     * @var MailChimpHelper
     */
    private $helper;
    /**
     * @var MailchimpNotificationCollectionFactory
     */
    private $mailchimpNotificationCollectionFactory;
    /**
     * @var MailchimpNotification
     */
    private $mailchimpNotification;
    /**
     * @var MailChimpHttp
     */
    private $mailchimpHttp;
    private $storeManager;
    const MAX_NOTIFICATIONS = 100;

    public function __construct(
        MailChimpHelper $helper,
        MailchimpNotificationCollectionFactory $mailchimpNotificationCollectionFactory,
        MailchimpNotification $mailchimpNotification,
        MailchimpHttp $mailchimpHttp,
        StoreManager $storeManager
    )
    {
        $this->helper = $helper;
        $this->mailchimpNotificationCollectionFactory = $mailchimpNotificationCollectionFactory;
        $this->mailchimpNotification = $mailchimpNotification;
        $this->mailchimpHttp = $mailchimpHttp;
        $mailchimpHttp->setUrl($helper->getConfigValue(MailChimpHelper::SYNC_NOTIFICATION_URL));
        $this->storeManager = $storeManager;
    }
    public function execute()
    {
        $count = 0;
        $this->helper->log("SyncStatistics");
        if ($this->helper->isSupportEnabled())
        {
            $scopeId = $this->storeManager->getDefaultStoreView()->getId();
            $scope = 'default';
            $token = $this->helper->getConfigValue(MailChimpHelper::XML_STATISTICS_TOKEN, $scopeId, $scope);
            if (!$token) {
                $this->helper->log("You must first register your copy to sync statistics");
                return;
            }
            $this->mailchimpHttp->setUrl($this->helper->getConfigValue(MailChimpHelper::XML_REGISTER_URL).'/logenabled');
            $response = $this->mailchimpHttp->get($token);
            $res = json_decode($response, true);
            if (key_exists('error',$res) && $res['error']) {
                $this->helper->log("Something went wrong while syncing statistics");
                return;
            } elseif (key_exists('enabled',$res) && !$res['enabled']) {
                $this->helper->log("You are not authorized to sync statistics");
                return;
            }
            $this->helper->log("Processing sync statistics");
            $this->mailchimpHttp->setUrl($this->helper->getConfigValue(MailChimpHelper::SYNC_NOTIFICATION_URL)."/$token");
            $collection = $this->getCollection();
            /**
             * @var $collectionItem \Ebizmarts\MailChimp\Model\MailChimpNotification
             */
            foreach ($collection as $collectionItem)
            {
                if($this->syncData($collectionItem->getNotificationData())) {
                    $collectionItem->setProcessed(true);
                    $collectionItem->setSyncedAt($this->helper->getGmtDate());
                    $collectionItem->getResource()->save($collectionItem);
                    $count++;
                } else {
                    break;
                }
            }
            $this->helper->log("Sync statistics $count registers processed");
        } else {
            $this->helper->log("Sync statistics not enabled");
        }
        $this->cleanData();
    }
    private function getCollection()
    {
        $collection = $this->mailchimpNotificationCollectionFactory->create();
        $collection->addFieldToFilter('processed', 0);
        $collection->setOrder('generated_at', 'ASC');
        $collection->getSelect()->limit(self::MAX_NOTIFICATIONS);;

        return $collection;
    }
    private function syncData($data)
    {
        $continue = true;
        $response = $this->mailchimpHttp->post($data);
        switch($this->mailchimpHttp->extractResponse($response)) {
            case MailchimpHttp::ERROR_GENERIC:
                break;
            case MailChimpHttp::ERROR_AUTH:
                $continue = false;
                break;
            case MailChimpHttp::ERROR_JSON:
                $this->helper->log("Invalid JSON, syncing process will continue regardless");
                break;
        }
        return $continue;
    }
    private function cleanData()
    {
        $days = $this->helper->getConfigValue(MailChimpHelper::XML_CLEAN_SUPPORT_PERIOD);
        try {
            $connection = $this->mailchimpNotification->getConnection();
            $tableName = $this->mailchimpNotification->getMainTable();
            $quoteInto = $connection->quoteInto('processed = 1 or date_add(generated_at, interval ? day) <= NOW()', $days);
            $connection->delete($tableName, $quoteInto);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
}
