<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Customer;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $interestGroupFactory;
    /**
     * @var SyncHelper
     */
    private $syncHelper;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        SyncHelper $syncHelper
    ) {
        $this->helper = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->interestGroupFactory = $interestGroupFactory;
        $this->syncHelper = $syncHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $request = $observer->getEvent()->getRequest();
        $allParams = $request->getParams();
        $subscriber = $this->subscriberFactory->create();
        if (isset($allParams['customer']['interestgroup'])) {
            $params = ['group' => $allParams['customer']['interestgroup']];
            foreach ($params['group'] as $index => $ig) {
                if (is_array($ig)) {
                    foreach ($ig as $i => $v) {
                        if ($v == 1) {
                            $params['group'][$index][$i] = $i;
                        }
                    }
                }
            }
            $interestGroup = $this->interestGroupFactory->create();
            try {
                $subscriber->loadBySubscriberEmail($customer->getEmail(), $customer->getStoreId());
                if ($subscriber->getEmail() == $customer->getEmail()) {
                    $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(), $subscriber->getStoreId());
                    $interestGroup->setGroupdata($this->helper->serialize($params));
                    $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                    $interestGroup->setStoreId($subscriber->getStoreId());
                    $interestGroup->setUpdatedAt($this->helper->getGmtDate());
                    $interestGroup->getResource()->save($interestGroup);
                    $this->syncHelper->markRegisterAsModified(
                        $subscriber->getId(),
                        \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER
                    );
                } else {
                    $this->subscriberFactory->create()->subscribe($customer->getEmail());
                    $subscriber->loadBySubscriberEmail($customer->getEmail(), $customer->getStoreId());
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
        } else {
            $subscriber->loadBySubscriberEmail($customer->getEmail(), $customer->getStoreId());
            if ($subscriber->getEmail() == $customer->getEmail()) {
                $this->syncHelper->markRegisterAsModified(
                    $subscriber->getId(),
                    \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER
                );
            }
        }
        $this->syncHelper->markRegisterAsModified($customer->getId(), \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER);
    }
}
