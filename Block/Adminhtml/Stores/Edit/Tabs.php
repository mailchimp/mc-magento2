<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/12/17 11:01 AM
 * @file: Tabs.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('stores_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Mailchimp Store Information'));
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'stores_info',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    \Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit\Tab\Info::class
                )->toHtml(),
                'active' => true
            ]
        );
        $this->addTab(
            'stores_address',
            [
                'label' => __('Address'),
                'title' => __('Address'),
                'content' => $this->getLayout()->createBlock(
                    \Ebizmarts\MailChimp\Block\Adminhtml\Stores\Edit\Tab\Address::class
                )->toHtml(),
                'active' => false
            ]
        );

        return parent::_beforeToHtml();
    }
}
