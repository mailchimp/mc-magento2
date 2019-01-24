<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/5/17 1:23 PM
 * @file: Edit.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

use Ebizmarts\MailChimp\Model\MailChimpStoresFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

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
