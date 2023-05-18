<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class Timeout implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [10 => 10, 20=> 20, 30=>30];
    }
}
