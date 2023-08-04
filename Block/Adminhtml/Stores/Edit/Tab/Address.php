<?php

namespace Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Address extends Generic implements TabInterface
{
    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_country;

    /**
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
        $model = $this->_coreRegistry->registry('mailchimp_stores');
        $form = $this->_formFactory->create();
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
                'name' => 'address_address_one',
                'label' => __('Street'),
                'required' => true
            ]
        );
        $fieldset->addField(
            'address_address_two',
            'text',
            [
                'name' => 'address_address_two',
                'label' => __('Street'),
                'required' => false
            ]
        );
        $fieldset->addField(
            'address_city',
            'text',
            [
                'name' => 'address_city',
                'label' => __('City'),
                'required' => true
            ]
        );
        $fieldset->addField(
            'address_postal_code',
            'text',
            [
                'name' => 'address_postal_code',
                'label' => __('Postal Code'),
                'required' => false
            ]
        );
        $country = $this->_country->toOptionArray();
        $countryArray = ['' => __('Select one')];
        foreach ($country as $c) {
            $countryArray[$c['value']] = $c['label'];
        }
        $fieldset->addField(
            'address_country_code',
            'select',
            [
                'name' => 'address_country_code',
                'label' => __('Country'),
                'required' => true,
                'options' => $countryArray
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
