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
            $this->helper->log($response);
        }
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
