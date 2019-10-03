<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/17/17 11:05 AM
 * @file: Save.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

class Save extends \Ebizmarts\MailChimp\Controller\Adminhtml\Stores
{
    public function execute()
    {
        $isPost = $this->getRequest()->getPost();
        if ($isPost) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $returnToEdit = (bool)$this->getRequest()->getParam('back', false);
            $storeModel = $this->_mailchimpStoresFactory->create();
            $formData = $this->getRequest()->getParam('stores');
            $storeId = isset($formData['id']) ? $formData['id'] : null;
            if ($storeId) {
                $storeModel->getResource()->load($storeModel, $storeId);
            }
            try {
                $formData['storeid'] = $this->_updateMailchimp($formData);
                $formData['platform'] = \Ebizmarts\MailChimp\Helper\Data::PLATFORM;
                $storeModel->setData($formData);
                $storeModel->getResource()->save($storeModel);
                if ($returnToEdit) {
                    if (!$storeId) {
                        $storeId = $storeModel->getId();
                    }
                    return $resultRedirect->setPath('mailchimp/stores/edit', ['id'=>$storeId]);
                } else {
                    return $resultRedirect->setPath('mailchimp/stores');
                }
            } catch (\Mailchimp_Error $e) {
                $this->messageManager->addErrorMessage(__('Store could not be saved.'.$e->getMessage()));
                $this->_helper->log($e->getFriendlyMessage());
                return $resultRedirect->setPath('mailchimp/stores/edit', ['id'=>$storeId]);
            }
        }
    }
    protected function _updateMailchimp($formData)
    {
        $api = $this->_helper->getApiByApiKey($formData['apikey'], true);
        // set the address
        $address = [];
        $address['address1']    = $formData['address_address_one'];
        $address['address2']    = $formData['address_address_two'];
        $address['city']        = $formData['address_city'];
        $address['province']    = '';
        $address['province_code'] = '';
        $address['postal_code'] = $formData['address_postal_code'];
        $address['country']     = '';
        $address['country_code'] = $formData['address_country_code'];
        $emailAddress   = $formData['email_address'];
        $currencyCode   = $formData['currency_code'];
        $primaryLocale  = $formData['primary_locale'];
        $timeZone       = $formData['timezone'];
        $phone          = $formData['phone'];
        $name           = $formData['name'];
        $domain         = $formData['domain'];
        $storeId = isset($formData['storeid']) ? $formData['storeid'] : null;
        $is_sync = null;

        if ($storeId) {
            $api->ecommerce->stores->edit(
                $storeId,
                \Ebizmarts\MailChimp\Helper\Data::PLATFORM,
                $domain,
                $name,
                $emailAddress,
                $currencyCode,
                null,
                $primaryLocale,
                $timeZone,
                $phone,
                $address,
                $is_sync
            );
        } else {
            $date = $this->_helper->getDateMicrotime();
            $mailchimpStoreId = hash('md5', $name. '_' . $date);
            //$mailchimpStoreId = md5($name. '_' . $date);
            $is_sync = true;
            $ret =$api->ecommerce->stores->add(
                $mailchimpStoreId,
                $formData['list_id'],
                $name,
                $currencyCode,
                \Ebizmarts\MailChimp\Helper\Data::PLATFORM,
                $domain,
                $emailAddress,
                null,
                $primaryLocale,
                $timeZone,
                $phone,
                $address,
                $is_sync
            );
            $formData['storeid'] = $mailchimpStoreId;
        }
        return $formData['storeid'];
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_edit');
    }
}
