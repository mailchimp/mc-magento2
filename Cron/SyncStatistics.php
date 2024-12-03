<?php

namespace Ebizmarts\MailChimp\Cron;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification\CollectionFactory as MailchimpNotificationCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification;
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
    public function __construct(
        MailChimpHelper $helper,
        MailchimpNotificationCollectionFactory $mailchimpNotificationCollectionFactory,
        MailchimpNotification $mailchimpNotification
    )
    {
        $this->helper = $helper;
        $this->mailchimpNotificationCollectionFactory = $mailchimpNotificationCollectionFactory;
        $this->mailchimpNotification = $mailchimpNotification;
    }
    public function execute()
    {
        $this->helper->log("Sync statistics started");
        if ($this->helper->isSupportEnabled())
        {
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
            }
        } else {
            $this->helper->log("Support is off");
        }
        $this->cleanData();
        $this->helper->log("Sync statistics finished");
    }
    private function getCollection()
    {
        $collection = $this->mailchimpNotificationCollectionFactory->create();
        $collection->addFieldToFilter('processed', 0);
        $collection->setOrder('generated_at', 'ASC');

        return $collection;
    }
    private function syncData($data)
    {
        $this->helper->log($data);
    }
    private function cleanData()
    {
        try {
            $connection = $this->mailchimpNotification->getConnection();
            $tableName = $this->mailchimpNotification->getMainTable();
            $connection->delete($tableName, ['date_add(generated_at , interval 1 week) <= NOW()']);
            $connection->delete($tableName, ['processed' => 1]);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
}
