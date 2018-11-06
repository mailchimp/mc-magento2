<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/23/17 4:40 PM
 * @file: Newsletter.php
 */

namespace Ebizmarts\MailChimp\Block;

class Newsletter extends \Magento\Framework\View\Element\Template
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
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Newsletter constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data
    ) {
    
        parent::__construct($context, $data);
        $this->_helper  = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerSession = $customerSession;
    }

    public function getInterest()
    {
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByCustomerId($this->customerSession->getCustomerId());
//        $subscriber = $this->getSubscriptionObject();
        return $this->_helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
    public function getFormUrl()
    {
        return  $this->getUrl('mailchimp/accountmanage/save');
    }
}
