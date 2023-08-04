<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class WebhookDelete implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Unsubscribe')],
            ['value' => 1, 'label' => __('Delete subscriber')]
        ];
    }
}
