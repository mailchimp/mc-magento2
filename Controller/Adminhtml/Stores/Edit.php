<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

class Edit extends \Ebizmarts\MailChimp\Controller\Adminhtml\Stores
{

    public function execute()
    {
        $storeId = $this->getRequest()->getParam('id');
        /** @var \Ebizmarts\MailChimp\Model\MailChimpStores $model */
        $model = $this->_mailchimpStoresFactory->create();

        if ($storeId) {
            $model->getResource()->load($model, $storeId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This store no longer exists.'));
                $this->_redirect('*/*/');

                return;
            }
        }

        // Restore previously entered form data from session
        $data = $this->_session->getStoreData(true);
        if (isset($data['name'])) {
            $data['name'] = preg_replace('/ \(Warning: not connected\)/', '', $data['name']);
        }
        if (!empty($data)) {
            $model->setData($data);
        }
        if (isset($model['name'])) {
            $model['name'] = preg_replace('/ \(Warning: not connected\)/', '', $model['name']);
        }
        $this->_coreRegistry->register('mailchimp_stores', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Ebizmarts_MailChimp::main_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Mailchimp Store'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_edit');
    }
}
