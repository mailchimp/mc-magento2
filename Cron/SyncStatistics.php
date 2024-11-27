<?php

namespace Ebizmarts\MailChimp\Cron;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification\CollectionFactory as MailchimpNotificationCollectionFactory;
class SyncStatistics
{
    private $helper;
    private $mailchimpNotificationCollectionFactory;
    public function __construct(
        MailChimpHelper $helper,
        MailchimpNotificationCollectionFactory $mailchimpNotificationCollectionFactory
    )
    {
        $this->helper = $helper;
        $this->mailchimpNotificationCollectionFactory = $mailchimpNotificationCollectionFactory;
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

    }
}
