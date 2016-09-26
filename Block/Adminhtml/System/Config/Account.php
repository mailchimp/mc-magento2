<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class Account extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $values = $element->getValues();

        $html = '<ul class="checkboxes">';
        if ($values) {
            foreach ($values as $dat) {
                $html .= "<li>{$dat['value']}: {$dat['label']}</li>";
            }
        }

        $html .= '</ul>';

        return $html;
    }
}
