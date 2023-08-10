<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

use Ebizmarts\MailChimp\Helper\Data as MailchimpHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveBefore implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $sync = $product->getSync();
        if (!$sync && $product->getMailchimpSent() != MailchimpHelper::NEVERSYNC) {
            $product->setMailchimpSent(MailchimpHelper::NEEDTORESYNC);
            $product->setMailchimpSyncError(null);
        }

        return $this;
    }
}
