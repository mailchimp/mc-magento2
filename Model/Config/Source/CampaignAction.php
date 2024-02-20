<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class CampaignAction implements \Magento\Framework\Option\ArrayInterface
{
    private $actions = [
        ['value' => 'sent', 'label' => 'Sent'],
        ['value' => 'open', 'label' => 'Open'],
        ['value' => 'click', 'label' => 'Click']
    ];
    public function toOptionArray()
    {
        return $this->actions;
    }
}
