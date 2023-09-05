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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;

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
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Session
     */
    private $session;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        CustomerRepositoryInterface $customerRepository,
        Session $session,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_helper  = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->session = $session;
    }

    public function getInterest()
    {
        $customer = $this->getCurrentCustomer();
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByCustomer($customer->getId(),$customer->getStoreId());
        return $this->_helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
    public function getFormUrl()
    {
        return  $this->getUrl('mailchimp/accountmanage/save');
    }
    private function getCurrentCustomer()
    {
        $customerId = $this->getCurrentCustomerId();
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $customer;
    }
    private function getCurrentCustomerId(): int
    {
        return $this->session->getCustomerId();
    }
}
