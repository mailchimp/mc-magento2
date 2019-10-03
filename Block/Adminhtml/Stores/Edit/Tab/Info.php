<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/12/17 11:03 AM
 * @file: Info.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Info extends Generic implements TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Locale\Timezone
     */
    protected $_timezone;
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_yesno;
    /**
     * @var \Magento\Config\Model\Config\Source\Locale\Currency
     */
    protected $_currency;
    /**
     * @var \Magento\Config\Model\Config\Source\Locale
     */
    protected $_locale;
    /**
     * @var \Ebizmarts\MailChimp\Model\Config\Source\ApiKey
     */
    protected $_apikey;

    /**
     * Info constructor.
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\Locale\Timezone $timezone
     * @param \Magento\Config\Model\Config\Source\Yesno $yesno
     * @param \Magento\Config\Model\Config\Source\Locale\Currency $currency
     * @param \Magento\Config\Model\Config\Source\Locale $locale
     * @param \Ebizmarts\MailChimp\Model\Config\Source\ApiKey $apiKey
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Locale\Timezone $timezone,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magento\Config\Model\Config\Source\Locale\Currency $currency,
        \Magento\Config\Model\Config\Source\Locale $locale,
        \Ebizmarts\MailChimp\Model\Config\Source\ApiKey $apiKey,
        array $data = []
    ) {
    
        $this->_timezone    = $timezone;
        $this->_yesno       = $yesno;
        $this->_currency    = $currency;
        $this->_locale      = $locale;
        $this->_apikey      = $apiKey;
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
            ['legend' => __('General')]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                ['name' => 'id']
            );
            $fieldset->addField(
                'apikey',
                'hidden',
                ['name' => 'apikey']
            );
            $fieldset->addField(
                'storeid',
                'hidden',
                ['name' => 'storeid']
            );
        } else {
            $this->_apikey->getAllApiKeys();
            $apikey = $this->_apikey->toOptionArray();
            $apikeyArray = [];
            foreach ($apikey as $a) {
                $apikeyArray[$a['value']] = $a['label'];
            }
            $url = $this->_urlBuilder->getUrl('mailchimp/stores/getList');
            $fieldset->addField(
                'apikey',
                'select',
                [
                    'name'      => 'apikey',
                    'label'     => __('Apikey'),
                    'required'  => true,
                    'options'   => $apikeyArray,
                ]
            );
            $listArray = [''=>__('Select first an ApiKey')];
            $fieldset->addField(
                'list_id',
                'select',
                [
                    'name'      => 'list_id',
                    'label'     => __('List'),
                    'required'  => true,
                    'options'   => $listArray,
                ]
            );
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'name'        => 'name',
                'label'    => __('Name'),
                'required'     => true
            ]
        );

        $fieldset->addField(
            'domain',
            'text',
            [
                'name'        => 'domain',
                'label'    => __('Domain'),
                'required'     => true
            ]
        );

        $fieldset->addField(
            'email_address',
            'text',
            [
                'name'        => 'email_address',
                'label'    => __('Email'),
                'required'     => true
            ]
        );
        $currency = $this->_currency->toOptionArray();
        $currencyArray = [''=> __('Select one')];
        foreach ($currency as $c) {
            $currencyArray[$c['value']] = $c['label'];
        }
        $fieldset->addField(
            'currency_code',
            'select',
            [
                'name'      => 'currency_code',
                'label'     => __('Currency'),
                'required'  => true,
                'options'   => $currencyArray
            ]
        );
        $locale = $this->_locale->toOptionArray();
        $localeArray = [''=> __('Select one')];
        foreach ($locale as $l) {
            $localeArray[$l['value']] = $l['label'];
        }

        $fieldset->addField(
            'primary_locale',
            'select',
            [
                'name'      => 'primary_locale',
                'label'     => __('Locale'),
                'required'  => true,
                'options'   => $localeArray
            ]
        );
        $timezone = $this->_timezone->toOptionArray();
        $timezoneArray = [''=> __('Select one')];
        foreach ($timezone as $t) {
            $timezoneArray[$t['value']] = $t['label'];
        }
        $fieldset->addField(
            'timezone',
            'select',
            [
                'name'        => 'timezone',
                'label'    => __('TimeZone'),
                'required'     => true,
                'options'   => $timezoneArray
            ]
        );
        $fieldset->addField(
            'phone',
            'text',
            [
                'name'        => 'phone',
                'label'    => __('Phone'),
                'required'     => true
            ]
        );

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    public function getTabLabel()
    {
        return __('Store Info');
    }
    public function getTabTitle()
    {
        return __('Store Info');
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
