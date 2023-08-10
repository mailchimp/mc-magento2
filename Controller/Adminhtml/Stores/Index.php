<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

use Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

class Index extends Stores
{
    public function execute()
    {
        $this->_mhelper->loadStores();
        $page = $this->_resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Mailchimp Stores'));

        return $page;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_grid');
    }
}
