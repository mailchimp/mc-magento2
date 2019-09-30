<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/8/17 5:07 PM
 * @file: SafeAfter.php
 */
namespace Ebizmarts\MailChimp\Observer\Customer;

use Magento\Framework\Event\Observer;

class SaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * SaveBefore constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {

        $this->_helper              = $helper;
        $this->subscriberFactory    = $subscriberFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $customer \Magento\Customer\Model\Customer
         */
        $customer = $observer->getCustomer();
        $storeId  = $customer->getStoreId();
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE)) {
            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE)) {
                $mailchimpStoreId = $this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                    $storeId
                );
                $this->_helper->saveEcommerceData(
                    $mailchimpStoreId,
                    $customer->getId(),
                    \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER,
                    null,
                    null,
                    1
                );
            }
            $subscriber = $this->subscriberFactory->create();
            $subscriber->loadByEmail($customer->getEmail());
            if ($subscriber->getEmail() == $customer->getEmail()) {
                $this->_helper->markRegisterAsModified(
                    $subscriber->getId(),
                    \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER
                );
            }
        }
    }
}
