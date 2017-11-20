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
     * Success constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
    }

    public function getInterest()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $interest = $this->_helper->getInterest($order->getStoreId());
        return $interest;
    }
    protected function getValues($category)
    {
        $rc =[];
        foreach($category as $c) {
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

}