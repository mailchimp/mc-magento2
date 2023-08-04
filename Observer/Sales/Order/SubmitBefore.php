<?php

namespace Ebizmarts\MailChimp\Observer\Sales\Order;

class SubmitBefore implements \Magento\Framework\Event\ObserverInterface
{
    private $attributes = [
        'mailchimp_abandonedcart_flag',
        'mailchimp_campaign_id',
        'mailchimp_landing_page'
    ];

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        $flag = 0;

        foreach ($this->attributes as $attribute) {
            if ($quote->hasData($attribute)) {
                $order->setData($attribute, $quote->getData($attribute));
                if ($quote->getData($attribute)) {
                    $flag = 1;
                }
            }
        }
        $order->setData('mailchimp_flag', $flag);

        return $this;
    }
}
