<?php

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class Date extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDateFormat(\Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);

        return parent::render($element);
    }
}
