<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/12/17 5:07 PM
 * @file: Address.php
 */
namespace Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Address extends Generic implements TabInterface
{
    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_country;

    /**
     * Address constructor.
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param \Magento\Directory\Model\Config\Source\Country $country
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Directory\Model\Config\Source\Country $country,
        array $data = []
    ) {
    
        $this->_country = $country;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $model  = $this->_coreRegistry->registry('mailchimp_stores');
        $form   = $this->_formFactory->create();
        $form->setHtmlIdPrefix('stores_');
        $form->setFieldNameSuffix('stores');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Address')]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                ['name' => 'id']
            );
        }
        $fieldset->addField(
            'address_address_one',
            'text',
            [
                'name'        => 'address_address_one',
                'label'    => __('Street'),
                'required'     => true
            ]
        );
        $fieldset->addField(
            'address_address_two',
            'text',
            [
                'name'        => 'address_address_two',
                'label'    => __('Street'),
                'required'     => false
            ]
        );
        $fieldset->addField(
            'address_city',
            'text',
            [
                'name'        => 'address_city',
                'label'    => __('City'),
                'required'     => true
            ]
        );
        $fieldset->addField(
            'address_postal_code',
            'text',
            [
                'name'        => 'address_postal_code',
                'label'    => __('Postal Code'),
                'required'     => false
            ]
        );
        $country = $this->_country->toOptionArray();
        $countryArray = [''=> __('Select one')];
        foreach ($country as $c) {
            $countryArray[$c['value']] = $c['label'];
        }
        $fieldset->addField(
            'address_country_code',
            'select',
            [
                'name'        => 'address_country_code',
                'label'    => __('Country'),
                'required'     => true,
                'options'   => $countryArray
            ]
        );

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    public function getTabLabel()
    {
        return __('Store Address Info');
    }
    public function getTabTitle()
    {
        return __('Store Address Info');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }
}
