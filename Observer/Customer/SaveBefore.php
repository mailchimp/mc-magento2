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
     * SaveBefore constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {

        $this->_helper              = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $customer \Magento\Customer\Model\Customer
         */
        $customer = $observer->getCustomer();
        $storeId  = $customer->getStoreId();
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $storeId);
        $this->_helper->saveEcommerceData($mailchimpStoreId, $customer->getId(), \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER, null, null, 1);
    }
}
