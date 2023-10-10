<?php

namespace Ebizmarts\MailChimp\Observer\Subscriber;

use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;

class SaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    )
    {
        $this->customerSession = $customerSession;
    }
    public function execute(Observer $observer)
    {
        // TODO: Implement execute() method.
        $subscriber = $observer->getSubscriber();
        if ($this->customerSession->getPhone()) {
            $subscriber->setPhone($this->customerSession->getPhone());
            $this->customerSession->unsPhone();
        }
        return $subscriber;
    }
}
