<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/27/17 12:01 PM
 * @file: CustomerMap.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Form\Field;

class CustomerMap extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var VarsMap
     */
    protected $_varsRenderer=null;
    protected $_mailchimpRenderer=null;

    protected function _getVarsRenderer()
    {
        if (!$this->_varsRenderer) {
            $this->_varsRenderer = $this->getLayout()->createBlock(
                \Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Form\Field\VarsMap::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_varsRenderer->setClass('customer_field_select');
        }
        return $this->_varsRenderer;
    }
    protected function _getMailchimpRenderer()
    {
        if (!$this->_mailchimpRenderer) {
            $this->_mailchimpRenderer = $this->getLayout()->createBlock(
                \Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Form\Field\MailchimpMap::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_mailchimpRenderer->setClass('mailchimp_field_select');
        }
        return $this->_mailchimpRenderer;
    }

    protected function _prepareToRender()
    {
//        $this->addColumn('mailchimp', ['label' => __('Mailchimp')]);
        $this->addColumn(
            'mailchimp_field_id',
            ['label' => __('Mailchimp'), 'renderer' => $this->_getMailchimpRenderer()]
        );
        $this->addColumn(
            'customer_field_id',
            ['label' => __('Magento'), 'renderer' => $this->_getVarsRenderer()]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getVarsRenderer()->calcOptionHash($row->getData('customer_field_id'))] =
            'selected="selected"';
        $optionExtraAttr['option_' . $this->_getMailchimpRenderer()->calcOptionHash(
            $row->getData('mailchimp_field_id')
        )
        ] =
            'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
}
