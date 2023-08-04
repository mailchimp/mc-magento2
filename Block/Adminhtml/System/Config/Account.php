<?php

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class Account extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $values = $element->getValues();
        $html = '<div id="mailchimp_general_account_details">';
        $html .= '<ul class="checkboxes" id="mailchimp_general_account_details_ul">';
        if ($values) {
            foreach ($values as $dat) {
                if ($dat['value'] !== '') {
                    $html .= "<li>{$dat['label']}: {$dat['value']}</li>";
                } else {
                    $html .= "<li>{$dat['label']}</li>";
                }
            }
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
