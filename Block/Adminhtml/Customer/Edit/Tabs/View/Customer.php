<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/29/17 2:49 PM
 * @file: Customer.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\Customer\Edit\Tabs\View;
use Magento\Store\Model\StoreManagerInterface;

class Customer extends \Magento\Backend\Block\Template
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
     * @var \Magento\Backend\Model\Session
     */
    protected $session;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager,
        array $data
    ) {

        parent::__construct($context, $data);
        $this->helper               = $helper;
        $this->subscriberFactory    = $subscriberFactory;
        $this->session =$context->getBackendSession();
        $this->storeManager         = $storeManager;
    }

    public function getInterest()
    {
        $subscriber = $this->subscriberFactory->create();
        $customerData = $this->session->getCustomerData();
        $email = $customerData['account']['email'];
        $storeId = $customerData['account']['store_id'];
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $subscriber->loadBySubscriberEmail($email,$websiteId);
        return $this->helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
}
