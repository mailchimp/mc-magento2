<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/24/17 10:38 AM
 * @file: Index.php
 */
namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

use Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

class Index extends Stores
{
    public function execute()
    {
        $this->_helper->loadStores();
        $page = $this->_resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Mailchimp Stores'));
        return $page;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_grid');
    }
}
