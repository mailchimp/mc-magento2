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
    protected function _getAddressAtt()
    {
        $ret = [];
        $ret['default_shipping##zip']     = __('Shipping Zip Code');
        $ret['default_shipping##country'] = __('Shipping Country');
        $ret['default_shipping##city']    = __('Shipping City');
        $ret['default_shipping##state']   = __('Shipping State');
        $ret['default_shipping##telephone']   = __('Shipping Telephone');

        $ret['default_billing##zip']      = __('Billing Zip Code');
        $ret['default_billing##country']  = __('Billing Country');
        $ret['default_billing##city']     = __('Billing City');
        $ret['default_billing##state']    = __('Billing State');
        $ret['default_billing##telephone']    = __('Billing Telephone');

        return $ret;
    }

    protected function _getBindableAttributes()
    {
        $systemAtt = $this->_getCustomerAtt();
        $extraAtt = $this->_getAddressAtt();

        // Note: We cannot use array_merge here because we need to hold
        // numeric indexes as they are
        $ret = $systemAtt + $extraAtt;

        natsort($ret);
        return $ret;
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
            foreach ($this->_getBindableAttributes() as $attId => $attLabel) {
                $this->addOption($attId, $this->escapeHtmlAttr($attLabel));
            }
        }
        return parent::_toHtml();
    }
}
