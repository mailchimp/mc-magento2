<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/30/17 8:34 PM
 * @file: Webhook.php
 */

namespace Ebizmarts\MailChimp\Cron;

class Webhook
{
    const ACTION_DELETE         = 'delete';
    const ACTION_UNSUBSCRIBE    = 'unsub';

    const TYPE_SUBSCRIBE        = 'subscribe';
    const TYPE_UNSUBSCRIBE      = 'unsubscribe';
    const TYPE_CLEANED          = 'cleaned';
    const TYPE_UPDATE_EMAIL     = 'upemail';
    const TYPE_PROFILE          = 'profile';
    const BATCH_LIMIT           = 50;
    const NOT_PROCESSED         = 0;
    const PROCESSED_OK          = 1;
    const PROCESSED_WITH_ERROR  = 2;
    const DATA_WITH_ERROR       = 3;
    const DATA_NOT_CONVERTED    = 4;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory
     */
    protected $_webhookCollection;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customer;

    /**
     * Webhook constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollection
     * @param \Magento\Customer\Model\CustomerFactory $customer
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollection,
        \Magento\Customer\Model\CustomerFactory $customer
    ) {
    
        $this->_helper              = $helper;
        $this->_subscriberFactory   = $subscriberFactory;
        $this->_webhookCollection   = $webhookCollection;
        $this->_customer            = $customer;
    }
    public function execute()
    {
        $this->processWebhooks();
    }
    public function processWebhooks()
    {
        /**
         * @var $collection \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\Collection
         */
        $collection = $this->_webhookCollection->create();
        $collection->addFieldToFilter('processed', ['eq'=>self::NOT_PROCESSED]);
        $collection->getSelect()->limit(self::BATCH_LIMIT);
        /**
         * @var $item \Ebizmarts\MailChimp\Model\MailChimpWebhookRequest
         */
        foreach ($collection as $item) {
            try {
                $data = $this->_helper->unserialize($item->getDataRequest());
                $stores = $this->_helper->getMagentoStoreIdsByListId($data['list_id']);
                if (count($stores)) {
                    switch ($item->getType()) {
                        case self::TYPE_SUBSCRIBE:
                            $this->_subscribe($data);
                            break;
                        case self::TYPE_UNSUBSCRIBE:
                            $this->_unsubscribe($data);
                            break;
                        case self::TYPE_CLEANED:
                            $this->_clean($data);
                            break;
                        case self::TYPE_UPDATE_EMAIL:
                            $this->_updateEmail($data);
                            break;
                        case self::TYPE_PROFILE:
                            $this->_profile($data);
                    }
                    $processed = self::PROCESSED_OK;
                } else {
                    $processed = self::PROCESSED_WITH_ERROR;
                }
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
                $processed = self::PROCESSED_WITH_ERROR;
            }
            $item->setProcessed($processed);
            $item->getResource()->save($item);
        }
    }
    protected function _subscribe($data)
    {
        $listId = $data['list_id'];
        $email  = $data['email'];
        $subscribers = $this->_helper->loadListSubscribers($listId, $email);
        /**
         * @var $sub \Magento\Newsletter\Model\Subscriber
         */
        if ($subscribers->count()) {
            foreach ($subscribers as $sub) {
                if ($sub->getSubscriberStatus() != \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                    $sub->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
                    $sub->getResource()->save($sub);
                }
            }
        } else {
            $storeIds = $this->_helper->getMagentoStoreIdsByListId($listId);
            if (count($storeIds) > 0) {
                foreach ($storeIds as $storeId) {
                    $sub = $this->_subscriberFactory->create();
                    $sub->setStoreId($storeId);
                    $sub->setSubscriberEmail($email);
                    $this->_subscribeMember($sub, \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
                }
            } else {
                $sub = $this->_subscriberFactory->create();
                $sub->setSubscriberEmail($email);
                $this->_subscribeMember($sub, \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
            }
        }
    }
    protected function _unsubscribe($data)
    {
        $listId = $data['list_id'];
        $email  = $data['email'];
        $subscribers = $this->_helper->loadListSubscribers($listId, $email);
        /**
         * @var $sub \Magento\Newsletter\Model\Subscriber
         */
        foreach ($subscribers as $sub) {
            try {
                $action = isset($data['action']) ? $data['action'] : self::ACTION_DELETE;
                switch ($action) {
                    case self::ACTION_DELETE:
                        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_WEBHOOK_DELETE)) {
                            $sub->getResource()->delete($sub);
                        } elseif ($sub->getSubscriberStatus()!=
                            \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED) {
                            $this->_subscribeMember(
                                $sub,
                                \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED
                            );
                        }
                        break;
                    case self::ACTION_UNSUBSCRIBE:
                        if ($sub->getSubscriberStatus()!=\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED) {
                            $this->_subscribeMember($sub, \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED);
                        }
                        break;
                }
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
    }
    protected function _clean($data)
    {
        $subscribers = $this->_helper->loadListSubscribers($data['list_id'], $data['email']);
        /**
         * @var $sub \Magento\Newsletter\Model\Subscriber
         */
        foreach ($subscribers as $sub) {
            $sub->getResource()->delete($sub);
        }
    }
    protected function _updateEmail($data)
    {
        $oldEmail = $data['old_email'];
        $newEmail = $data['new_email'];
        $listId   = $data['list_id'];
        $oldSubscribers = $this->_helper->loadListSubscribers($listId, $oldEmail);
        $newSubscribers = $this->_helper->loadListSubscribers($listId, $newEmail);
        /**
         * @var $sub \Magento\Newsletter\Model\Subscriber
         */
        if (!$newSubscribers->count()) {
            if ($oldSubscribers->count()) {
                foreach ($oldSubscribers as $sub) {
                    $sub->setSubscriberEmail($newEmail);
                    $sub->getResource()->save($sub);
                }
            } else {
                $sub = $this->_subscriberFactory->create();
                $sub->setSubscriberEmail($newEmail);
                $this->_subscribeMember($sub, \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
            }
        }
    }
    protected function _profile($data)
    {
        $listId = $data['list_id'];
        $email = $data['email'];
        $fname = isset($data['merges']['FNAME']) ? $data['merges']['FNAME'] : null;
        $lname = isset($data['merges']['LNAME']) ? $data['merges']['LNAME'] : null;
        $customers = $this->_helper->loadListCustomers($listId, $email);
        if ($customers->count() > 0) {
            /**
             * @var $customer  \Magento\Customer\Model\Customer
             */
            foreach ($customers as $c) {
                $customer = $this->_customer->create();
                $customer->getResource()->load($customer, $c->getEntityId());
                /**
                 * @todo change the merge vars
                 */
                $fname = trim($fname);
                $lname = trim($lname);
                if ($fname != "") {
                    $customer->setFirstname($fname);
                }
                if ($lname != "") {
                    $customer->setLastname($lname);
                }
                $customer->getResource()->save($customer);
            }
        } else {
            $subscribers = $this->_helper->loadListSubscribers($listId, $email);
            if ($subscribers->count() == 0) {
                $subscriber = $this->_subscriberFactory->create();
                $subscriber->setSubscriberEmail($email);

                $stores = $this->_helper->getMagentoStoreIdsByListId($listId);
                if (count($stores)) {
                    $subscriber->setStoreId($stores[0]);
                    try {
                        $api = $this->_helper->getApi($stores[0]);
                        $member = $api->lists->members->get($listId, hash('md5', $email));
                        if ($member) {
                            if ($member['status'] == \Mailchimp::SUBSCRIBED) {
                                $this->_subscribeMember(
                                    $subscriber,
                                    \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
                                );
                            } elseif ($member['status'] == \Mailchimp::UNSUBSCRIBED) {
                                $this->_subscribeMember(
                                    $subscriber,
                                    \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED
                                );
                            }
                        }
                    } catch (\Mailchimp_Error $e) {
                        $this->_helper->log($e->getFriendlyMessage());
                    }
                }
            }
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param string $status
     * @throws \Exception
     */
    protected function _subscribeMember(\Magento\Newsletter\Model\Subscriber $subscriber, int $status)
    {
        $subscriber->setImportMode(true);
        $subscriber->setStatus($status);
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
        $subscriber->setIsStatusChanged(true);
        $subscriber->getResource()->save($subscriber);
    }
}
