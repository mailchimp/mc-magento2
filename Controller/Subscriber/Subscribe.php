<?php

namespace Ebizmarts\MailChimp\Controller\Subscriber;

use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Controller\Subscriber\NewAction as SubscriberController;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Subscribe extends SubscriberController implements HttpPostActionInterface
{
    private $session;
    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl,
        CustomerAccountManagement $customerAccountManagement,
        SubscriptionManagerInterface $subscriptionManager,
        EmailValidator $emailValidator = null,
        CustomerRepositoryInterface $customerRepository = null
    )
    {
        $this->session = $customerSession;
        parent::__construct($context, $subscriberFactory, $customerSession, $storeManager, $customerUrl, $customerAccountManagement, $subscriptionManager, $emailValidator, $customerRepository);
    }

    public function execute()
    {
        if($this->getRequest()->isPost() && $this->getRequest()->getPost('phone')) {
            $email = (string)$this->getRequest()->getPost('email');
            $phone = (string)$this->getRequest()->getPost('phone');
            $this->session->setPhone($phone);
        }
        return parent::execute();
    }
}
