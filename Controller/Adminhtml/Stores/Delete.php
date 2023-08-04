<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

class Delete extends \Ebizmarts\MailChimp\Controller\Adminhtml\Stores
{
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $storeId = (int)$this->getRequest()->getParam('id');
        if ($storeId) {
            $storeModel = $this->_mailchimpStoresFactory->create();
            $storeModel->getResource()->load($storeModel, $storeId);
            try {
                $api = $this->_mhelper->getApiByApiKey($storeModel->getApikey(), true);
                $api->ecommerce->stores->delete($storeModel->getStoreid());
                $this->messageManager->addSuccess(__('You deleted the store.'));

                return $resultRedirect->setPath('mailchimp/stores');
            } catch (\Mailchimp_Error $e) {
                $this->messageManager->addError(__('Store could not be deleted.' . $e->getMessage()));
                $this->_mhelper->log($e->getFriendlyMessage());

                return $resultRedirect->setPath('mailchimp/stores/edit', ['id' => $storeId]);
            }
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_edit');
    }
}
