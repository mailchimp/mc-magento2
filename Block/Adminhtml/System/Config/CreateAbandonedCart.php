<?php

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class CreateAbandonedCart extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'system/config/create_abandonedcart_automation.phtml';

    private $_url = "https://admin.mailchimp.com/#/create-campaign/explore/abandonedCart";

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $label = $originalData['button_label'];

        $this->addData([
            'button_label' => __($label),
            'button_url' => $this->authorizeRequestUrl(),
            'html_id' => $element->getHtmlId(),
        ]);

        return parent::_toHtml();;
    }

    public function authorizeRequestUrl()
    {
        return $this->_url;
    }
}
