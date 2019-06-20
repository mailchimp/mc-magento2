<?php
/**
 * Created by PhpStorm.
 * User: gonzalo
 * Date: 3/12/18
 * Time: 2:12 PM
 */
namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config;

class ResyncSubscriber extends \Magento\Config\Block\System\Config\Form\Field
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
        $this->setTemplate('system/config/resyncsubscriber.phtml');
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
            ]
        );
        return $this->_toHtml();
    }
}
