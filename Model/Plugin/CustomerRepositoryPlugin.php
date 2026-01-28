<?php

namespace Ebizmarts\MailChimp\Model\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

class CustomerRepositoryPlugin
{
    private $customerExtensionFactory;
    private $mailChimpInterestGroupFactory;
    private $subscriberFactory;
    private $mailChimpHelper;
    public function __construct(
        CustomerExtensionFactory $customerExtensionFactory,
        MailChimpInterestGroupFactory $mailChimpInterestGroupFactory,
        SubscriberFactory $subscriberFactory,
        MailChimpHelper $mailChimpHelper
    ) {
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->mailChimpInterestGroupFactory = $mailChimpInterestGroupFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->mailChimpHelper = $mailChimpHelper;
    }
    public function afterGet(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $result
    )
    {
        $extensionAttributes = $result->getExtensionAttributes()?: $this->customerExtensionFactory->create();
        $extensionAttributes->setMailchimpGroups($result->getMailchimpGroups());
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }
    public function beforeSave(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $customer
    ) {
        $extensionAttributes = $customer->getExtensionAttributes()?: $this->customerExtensionFactory->create();
        if ($extensionAttributes !== null && $extensionAttributes->getMailchimpGroups() !== null) {
            $customer->setMailchimpGroups($extensionAttributes->getMailchimpGroups());
        }
        return [$customer];
    }
    public function afterSave(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterface $customer
    ) {
        $this->mailChimpHelper->log(__METHOD__);
        $extensionAttributes = $customer->getExtensionAttributes()?: $this->customerExtensionFactory->create();
        if ($extensionAttributes !== null && $extensionAttributes->getMailchimpGroups() !== null) {
            /**
             * @var MailChimpInterestGroup $mailchimpGroups
             */
            $subscriber = $this->subscriberFactory->create()->loadByCustomer($customer->getId(), $customer->getWebsiteId());
            if ($subscriber->isSubscribed()) {
                $mailchimpGroups = $this->mailChimpInterestGroupFactory->create();
                $mailchimpGroups->setGroupdata($this->mailChimpHelper->serialize($extensionAttributes->getMailchimpGroups()));
                $mailchimpGroups->setSubscriberId($subscriber->getId());
                $mailchimpGroups->setUpdatedAt($this->mailChimpHelper->getGmtDate());
                $mailchimpGroups->save();
            } else {
                $this->mailChimpHelper->log('Not a subscriber');
            }
        }
    }
}
