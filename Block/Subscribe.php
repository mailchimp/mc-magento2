<?php

namespace Ebizmarts\MailChimp\Block;

use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\View\Element\Template;
use \Ebizmarts\MailChimp\Helper\Data as MailchimpHelper;

class Subscribe extends \Magento\Newsletter\Block\Subscribe
{
    /**
     * @var Template\Context
     */
    protected $context;
    /**
     * @var MailchimpHelper
     */
    protected $helper;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepo;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @param Template\Context $context
     * @param MailchimpHelper $helper
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepo
     * @param CustomerFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        MailchimpHelper $helper,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepo,
        CustomerFactory $customerFactory,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->customerRepo = $customerRepo;
        $this->customerFactory = $customerFactory;
    }

    public function getPopupUrl()
    {
        $storeId = $this->context->getStoreManager()->getStore()->getId();
        return $this->helper->getConfigValue(MailchimpHelper::XML_POPUP_URL,$storeId);
    }
    public function getFormActionUrl()
    {
        return $this->getUrl('mailchimp/subscriber/subscribe', ['_secure' => true]);
    }
    public function showMobilePhone()
    {
        $ret = true;
        if ($this->customerSession->getCustomerId()) {
            /**
             * @var $customer \Magento\Customer\Model\Customer
             */
            $customer = $this->customerFactory->create()->load($this->customerSession->getCustomerId());
            $mobilePhone = $customer->getData('mobile_phone');
            if ($mobilePhone&&$mobilePhone!='') {
                $ret = false;
            }
        }
        return $ret;
    }
}
