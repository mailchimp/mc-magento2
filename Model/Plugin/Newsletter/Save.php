<?php

namespace Ebizmarts\MailChimp\Model\Plugin\Newsletter;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class Save
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $interestGroupFactory;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->helper = $helper;
        $this->syncHelper = $syncHelper;
        $this->customerSession = $customerSession;
        $this->subscriberFactory = $subscriberFactory;
        $this->request = $request;
        $this->interestGroupFactory = $interestGroupFactory;
    }

    public function afterExecute()
    {
        $params = $this->request->getParams();

        $subscriber = $this->subscriberFactory->create();
        $interestGroup = $this->interestGroupFactory->create();
        /**
         * @var $customer \Magento\Customer\Model\Customer
         */
        $customer = $this->customerSession->getCustomer();

        $email = $customer->getEmail();

        try {
            $subscriber->loadByCustomer($customer->getId(), $customer->getStoreId());
            if ($subscriber->getEmail() == $email) {
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(), $subscriber->getStoreId());
                $interestGroup->setGroupdata($this->helper->serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->helper->getGmtDate());
                $interestGroup->getResource()->save($interestGroup);
                $listId = $this->helper->getGeneralList($subscriber->getStoreId());
                $this->_updateSubscriber($listId, $subscriber->getId(), $this->helper->getGmtDate(), null, 1);
            } else {
                $this->subscriberFactory->create()->subscribe($email);
                $subscriber->loadBySubscriberEmail($email, $customer->getStoreId());
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(), $subscriber->getStoreId());
                $interestGroup->setGroupdata($this->helper->serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->helper->getGmtDate());
                $interestGroup->getResource()->save($interestGroup);
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            $this->helper->log($params);
        }
    }

    protected function _updateSubscriber(
        $listId,
        $entityId,
        $sync_delta = null,
        $sync_error = null,
        $sync_modified = null
    ) {
        $this->syncHelper->saveEcommerceData(
            $listId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}
