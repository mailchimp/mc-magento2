<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;
use \Magento\Framework\Data\OptionSourceInterface;

class CleanPeriod implements OptionSourceInterface
{
    public function toOptionArray() {
        return [1 => 'One day', 3=> 'Three days', 7=> 'One week'];
    }
}
