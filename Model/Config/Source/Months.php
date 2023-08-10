<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class Months implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return ["0" => __("No"), "1" => "1", "2" => "2", "3" => "3", "4" => "4"];
    }
}
