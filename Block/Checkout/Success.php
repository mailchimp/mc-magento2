<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/13/17 4:41 PM
 * @file: Success.php
 */
namespace Ebizmarts\MailChimp\Block\Checkout;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $_interestGroupFactory;
    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $_context;

    /**
     * Success constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        array $data
    ) {
    
        parent::__construct($context, $data);
        $this->_checkoutSession     = $checkoutSession;
        $this->_helper              = $helper;
        $this->_subscriberFactory   = $subscriberFactory;
        $this->_interestGroupFactory= $interestGroupFactory;
        $this->_context             = $context;
    }

    public function getInterest()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         */
        $subscriber = $this->_subscriberFactory->create();
        $subscriber->loadByEmail($order->getCustomerEmail());

        return $this->_helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
    protected function getValues($category)
    {
        $rc =[];
        foreach ($category as $c) {
            $rc[] = ['value'=>$c['id'],'label'=>$c['name']];
        }
        return $rc;
    }
    public function getMessageBefore()
    {
        return $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_INTEREST_SUCCESS_HTML_BEFORE);
    }
    public function getMessageAfter()
    {
        return $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_INTEREST_SUCCESS_HTML_AFTER);
    }
    public function getFormUrl()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        return $this->_helper->getSuccessInterestUrl($order->getStoreId());
    }
    public function _toHtml()
    {
        if (!$this->_helper->isMailChimpEnabled($this->_context->getStoreManager()->getStore()->getId())) {
            return "";
        }
        return parent::_toHtml();
    }
}
