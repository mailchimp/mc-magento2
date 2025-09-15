<?php

namespace Ebizmarts\MailChimp\Cron;

use Magento\Store\Model\StoreManager;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification\CollectionFactory as MailchimpNotificationCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification;
use Ebizmarts\MailChimp\Model\MailchimpNotificationFactory;
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
     * @var MailchimpNotificationFactory
     */
    private $mailchimpNotificationFactory;
    /**
     * @var MailchimpNotification
     */
    private $mailchimpNotification;
    /**
     * @var MailChimpHttp
     */
    private $mailchimpHttp;
    private $storeManager;
    const MAX_NOTIFICATIONS = 200;
    const MAX_BATCH_SIZE = 25;

    public function __construct(
        MailChimpHelper $helper,
        MailchimpNotificationCollectionFactory $mailchimpNotificationCollectionFactory,
        MailchimpNotificationFactory $mailchimpNotificationFactory,
        MailchimpNotification $mailchimpNotification,
        MailchimpHttp $mailchimpHttp,
        StoreManager $storeManager
    )
    {
        $this->helper = $helper;
        $this->mailchimpNotificationCollectionFactory = $mailchimpNotificationCollectionFactory;
        $this->mailchimpNotificationFactory = $mailchimpNotificationFactory;
        $this->mailchimpNotification = $mailchimpNotification;
        $this->mailchimpHttp = $mailchimpHttp;
        $mailchimpHttp->setUrl($helper->getConfigValue(MailChimpHelper::SYNC_NOTIFICATION_URL));
        $this->storeManager = $storeManager;
    }
    public function execute()
    {
        $count = 0;
        $batch = [];
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
            $url = $this->helper->getConfigValue(MailChimpHelper::SYNC_NOTIFICATION_URL)."/$token";
            $this->mailchimpHttp->setUrl($url);
            $collection = $this->getCollection();
            /**
             * @var $collectionItem \Ebizmarts\MailChimp\Model\MailChimpNotification
             */
            foreach ($collection as $collectionItem)
            {
                if (!($count%self::MAX_BATCH_SIZE)&&$count) {
                    if ($this->syncData($batch)) {
                        $batch = [];
                        $batch[$collectionItem->getId()] = $collectionItem->getNotificationData();
                        $count++;
                    } else {
                        $batch = [];
                        break;
                    }
                } else {
                    $batch[$collectionItem->getId()] = $collectionItem->getNotificationData();
                    $count++;
                }
            }
            if (count($batch)) {
                $this->syncData($batch);
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
        $this->helper->log("SyncDada");
        $batch = [];
        $continue = true;
        foreach ($data as $id => $notification) {
            $json = [];
            try {
                $jsonData = json_decode($notification, true, 50, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
                $mailchimpNotification = $this->mailchimpNotificationFactory->create();
                $mailchimpNotification->getResource()->load($mailchimpNotification, $id);
                $mailchimpNotification->setProcessed(true);
                $mailchimpNotification->setSyncedAt($this->helper->getGmtDate());
                $mailchimpNotification->getResource()->save($mailchimpNotification);
                continue;
            }
            $json['data'] = $jsonData;
            $json['id'] = $id;
            $batch[] = $json;
        }
        $post = json_encode($batch);
        try {
            $response = $this->mailchimpHttp->post($post);
            $rjson = json_decode($response, true);
            if (is_array($rjson)) {
                foreach ($rjson as $r) {
                    if (is_array($r) && array_key_exists('id', $r)) {
                        $id = $r['id'];
                        if (!$r['error']) {
                            $mailchimpNotification = $this->mailchimpNotificationFactory->create();
                            $mailchimpNotification->getResource()->load($mailchimpNotification, $id);
                            $mailchimpNotification->setProcessed(true);
                            $mailchimpNotification->setSyncedAt($this->helper->getGmtDate());
                            $mailchimpNotification->getResource()->save($mailchimpNotification);
                        }
                    } else {
                        $this->helper->log("Sync notification failed to sync");
                        $this->helper->log($r);
                    }
                }
            } else {
                switch ($this->mailchimpHttp->extractResponse($response)) {
                    case MailchimpHttp::ERROR_GENERIC:
                        break;
                    case MailChimpHttp::ERROR_AUTH:
                        $continue = false;
                        break;
                    case MailChimpHttp::ERROR_JSON:
                        $this->helper->log("Invalid JSON, syncing process will continue regardless");
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->helper->log('Exception '.$e->getMessage());
            return false;
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
