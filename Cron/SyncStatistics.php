<?php

namespace Ebizmarts\MailChimp\Cron;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification\CollectionFactory as MailchimpNotificationCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;
use const _PHPStan_7c8075089\__;

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
    const MAX_NOTIFICATIONS = 100;

    public function __construct(
        MailChimpHelper $helper,
        MailchimpNotificationCollectionFactory $mailchimpNotificationCollectionFactory,
        MailchimpNotification $mailchimpNotification,
        MailchimpHttp $mailchimpHttp
    )
    {
        $this->helper = $helper;
        $this->mailchimpNotificationCollectionFactory = $mailchimpNotificationCollectionFactory;
        $this->mailchimpNotification = $mailchimpNotification;
        $this->mailchimpHttp = $mailchimpHttp;
    }
    public function execute()
    {
        $count = 0;
        $this->helper->log("SyncStatistics");
        if ($this->helper->isSupportEnabled())
        {
            $this->helper->log("Processing sync statistics");
            $collection = $this->getCollection();
            /**
             * @var $collectionItem \Ebizmarts\MailChimp\Model\MailChimpNotification
             */
            foreach ($collection as $collectionItem)
            {
                $this->syncData($collectionItem->getNotificationData());
                $collectionItem->setProcessed(true);
                $collectionItem->setSyncedAt($this->helper->getGmtDate());
                $collectionItem->getResource()->save($collectionItem);
                $count++;
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
        $response = $this->mailchimpHttp->post($data);
        if (!$this->mailchimpHttp->extractResponse($response)) {
            $this->helper->log("Invalid JSON, syncing process will continue regardless");
        }
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
