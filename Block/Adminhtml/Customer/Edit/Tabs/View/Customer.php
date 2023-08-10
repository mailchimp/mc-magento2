<?php


namespace Ebizmarts\MailChimp\Block\Adminhtml\Customer\Edit\Tabs\View;

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
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Registry $registry,
        array $data
    ) {

        parent::__construct($context, $data);
        $this->helper               = $helper;
        $this->subscriberFactory    = $subscriberFactory;
        $this->registry             = $registry;
    }

    public function getInterest()
    {
        $subscriber = $this->subscriberFactory->create();
        $customerId = $this->registry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
        $subscriber->loadByCustomer($customerId);
        return $this->helper->getSubscriberInterest($subscriber->getSubscriberId(), $subscriber->getStoreId());
    }
}
