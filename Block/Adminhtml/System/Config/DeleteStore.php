<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 2/20/17 3:25 PM
 * @file: ResetErrors.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class DeleteStore extends \Magento\Config\Block\System\Config\Form\Field
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
        $this->setTemplate('system/config/deletestore.phtml');
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
            'button_url'   => $this->getAjaxCheckUrl(),
            'html_id' => $element->getHtmlId(),
        ]);
        return $this->_toHtml();
    }
    public function getAjaxCheckUrl()
    {
        $params = $this->getRequest()->getParams();
        $scope = [];
        if (isset($params['website'])) {
            $scope = ['website'=>$params['website']];
        } elseif (isset($params['store'])) {
            $scope = ['store'=>$params['store']];
        }
        return $this->_urlBuilder->getUrl('mailchimp/ecommerce/DeleteStore', $scope);
    }
}
