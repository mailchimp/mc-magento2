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
declare(strict_types=1);

namespace Ebizmarts\MailChimp\Block\Adminhtml\Customer\Edit\Tabs\View;

class Customer extends \Magento\Backend\Block\Template
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\App\Emulation $emulation
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        array $data
    ) {
        parent::__construct($context, $data);

        $this->helper = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->registry = $registry;
        $this->emulation = $emulation;
        $this->customerRepository = $customerRepository;
    }

    public function getInterest()
    {
        $customerId = $this->registry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
        $customerData = $this->customerRepository->getById($customerId);
        $subscriber = $this->subscriberFactory->create();

        $this->emulation->startEnvironmentEmulation($customerData->getStoreId());
        $subscriber->loadByCustomerId($customerId);
        $this->emulation->stopEnvironmentEmulation();

        return $this->helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
}
