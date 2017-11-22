<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/20/17 5:06 PM
 * @file: Success.php
 */

namespace Ebizmarts\MailChimp\Controller\Checkout;



class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $_interestGroupFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * Success constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->_pageFactory         =$pageFactory;
        $this->_helper              = $helper;
        $this->_checkoutSession     = $checkoutSession;
        $this->_subscriberFactory   = $subscriberFactory;
        $this->_interestGroupFactory= $interestGroupFactory;
        $this->_date                = $date;
        parent::__construct($context);
    }

    public function execute()
    {
        $params     = $this->getRequest()->getParams();
        $order = $this->_checkoutSession->getLastRealOrder();
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         * @var $interestGroup \Ebizmarts\MailChimp\Model\MailChimpInterestGroup
         */
        $subscriber = $this->_subscriberFactory->create();
        $interestGroup = $this->_interestGroupFactory->create();
        try {
            $subscriber->loadByEmail($order->getCustomerEmail());
            if($subscriber->getEmail()==$order->getCustomerEmail()) {
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(),$order->getStoreId());
                $interestGroup->setGroupdata(serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->_date->gmtDate());
                $interestGroup->getResource()->save($interestGroup);
            } else {
                $this->_subscriberFactory->create()->subscribe($order->getCustomerEmail());
                $subscriber->loadByEmail($order->getCustomerEmail());
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(),$order->getStoreId());
                $interestGroup->setGroupdata(serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->_date->gmtDate());
                $interestGroup->getResource()->save($interestGroup);
            }
        } catch(\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
    }
}