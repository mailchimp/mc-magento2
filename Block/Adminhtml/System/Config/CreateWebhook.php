<?php
/**
 * Created by PhpStorm.
 * User: gonzalo
 * Date: 3/12/18
 * Time: 2:12 PM
 */
namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class CreateWebhook extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;

    /**
     * ResetErrors constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data = []
    ) {

        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/createwebhook.phtml');
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'button_url' => $this->getAjaxCheckUrl(),
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }

    public function getButtonHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $label = $originalData['button_label'];
        $this->addData([
            'button_label' => __($label),
            'button_url'   => $this->getAjaxCreateWebhookUrl(),
            'html_id' => $element->getHtmlId(),
        ]);
        return $this->_toHtml();
    }
    public function getAjaxCreateWebhookUrl()
    {
        $params = $this->getRequest()->getParams();
        $scope = [];
        if (isset($params['website'])) {
            $scope = ['website'=>$params['website']];
        } elseif (isset($params['store'])) {
            $scope = ['store'=>$params['store']];
        }
        return $this->_urlBuilder->getUrl('mailchimp/ecommerce/CreateWebhook', $scope);
    }
}
