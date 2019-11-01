<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/27/17 3:41 PM
 * @file: VarsMap.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Form\Field;

class VarsMap extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $_attCollection;
    /**
     * VarsMap constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_attCollection = $attCollection;
    }

    protected function _getCustomerAtt()
    {
        $ret = [];
        $collection = $this->_attCollection->create();
        /**
         * @var $item \Magento\Customer\Model\Attribute
         */
        foreach ($collection as $item) {
            $ret[$item->getId()] = $item->getFrontendLabel();
        }

        natsort($ret);
        return $ret;
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getCustomerAtt() as $attId => $attLabel) {
                $this->addOption($attId, $this->escapeHtmlAttr($attLabel));
            }
        }
        return parent::_toHtml();
    }
}
