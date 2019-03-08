<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/1/16 10:02 AM
 * @file: Ecommerce.php
 */

namespace Ebizmarts\MailChimp\Cron;

class Ecommerce
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Product
     */
    private $_apiProduct;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Result
     */
    private $_apiResult;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Customer
     */
    private $_apiCustomer;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Order
     */
    private $_apiOrder;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Cart
     */
    private $_apiCart;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
     */
    private $_mailChimpSyncBatches;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    private $_chimpSyncEcommerce;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Subscriber
     */
    private $_apiSubscribers;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\PromoCodes
     */
    private $_apiPromoCodes;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\PromoRules
     */
    private $_apiPromoRules;

    /**
     * Ecommerce constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Api\Product $apiProduct
     * @param \Ebizmarts\MailChimp\Model\Api\Result $apiResult
     * @param \Ebizmarts\MailChimp\Model\Api\Customer $apiCustomer
     * @param \Ebizmarts\MailChimp\Model\Api\Order $apiOrder
     * @param \Ebizmarts\MailChimp\Model\Api\Cart $apiCart
     * @param \Ebizmarts\MailChimp\Model\Api\Subscriber $apiSubscriber
     * @param \Ebizmarts\MailChimp\Model\Api\PromoCodes $apiPromoCodes
     * @param \Ebizmarts\MailChimp\Model\Api\PromoRules $apiPromoRules
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Product $apiProduct,
        \Ebizmarts\MailChimp\Model\Api\Result $apiResult,
        \Ebizmarts\MailChimp\Model\Api\Customer $apiCustomer,
        \Ebizmarts\MailChimp\Model\Api\Order $apiOrder,
        \Ebizmarts\MailChimp\Model\Api\Cart $apiCart,
        \Ebizmarts\MailChimp\Model\Api\Subscriber $apiSubscriber,
        \Ebizmarts\MailChimp\Model\Api\PromoCodes $apiPromoCodes,
        \Ebizmarts\MailChimp\Model\Api\PromoRules $apiPromoRules,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
    ) {

        $this->_storeManager    = $storeManager;
        $this->_helper          = $helper;
        $this->_apiProduct      = $apiProduct;
        $this->_mailChimpSyncBatches    = $mailChimpSyncBatches;
        $this->_apiResult       = $apiResult;
        $this->_apiCustomer     = $apiCustomer;
        $this->_apiOrder        = $apiOrder;
        $this->_apiCart         = $apiCart;
        $this->_apiSubscribers  = $apiSubscriber;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_apiPromoCodes   = $apiPromoCodes;
        $this->_apiPromoRules   = $apiPromoRules;
    }

    public function execute()
    {

        $connection = $this->_chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->_chimpSyncEcommerce->getResource()->getMainTable();
        $connection->delete($tableName, 'batch_id is null and mailchimp_sync_modified != 1');

        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
                if (!$this->_ping($storeId)) {
                    $this->_helper->log('MailChimp is not available');
                    return;
                }
                $this->_storeManager->setCurrentStore($storeId);
                $listId = $this->_helper->getGeneralList($storeId);
                $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $storeId);
                if ($mailchimpStoreId != -1 && $mailchimpStoreId != '') {
                    $this->_apiResult->processResponses($storeId, true, $mailchimpStoreId);
                    $batchId = $this->_processStore($storeId, $mailchimpStoreId, $listId);
                    if ($batchId) {
                        $connection->update($tableName, ['batch_id' => $batchId, 'mailchimp_sync_modified' => 0, 'mailchimp_sync_delta' => $this->_helper->getGmtDate()], "batch_id is null and mailchimp_store_id = '$mailchimpStoreId'");
                        $connection->update($tableName, ['batch_id' => $batchId, 'mailchimp_sync_modified' => 0, 'mailchimp_sync_delta' => $this->_helper->getGmtDate()], "batch_id is null and mailchimp_store_id = '$listId'");
                    }
                }
            }
        }
        $syncs = [];
        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $storeId);
            if ($mailchimpStoreId != -1 && $mailchimpStoreId != '') {
                $dateSync = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC, $storeId);
                if (isset($syncs[$mailchimpStoreId])) {
                    if ($syncs[$mailchimpStoreId] && $syncs[$mailchimpStoreId]['datesync'] < $dateSync) {
                        $syncs[$mailchimpStoreId]['datesync'] = $dateSync;
                        $syncs[$mailchimpStoreId]['storeid'] = $storeId;
                    }
                } elseif ($dateSync) {
                    $syncs[$mailchimpStoreId]['datesync'] = $dateSync;
                    $syncs[$mailchimpStoreId]['storeid'] = $storeId;
                } else {
                    $syncs[$mailchimpStoreId] = false;
                }
            }
        }
        foreach ($syncs as $mailchimpStoreId => $val) {
            if ($val && !$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC . "/$mailchimpStoreId", 0, 'default')) {
                $this->updateSyncFlagData($val['storeid'], $mailchimpStoreId);
            }
        }
    }

    protected function _processStore($storeId, $mailchimpStoreId, $listId)
    {
        $batchId = null;
        $countCustomers = 0;
        $countProducts = 0;
        $countOrders = 0;
        $batchArray = [];
        $this->_helper->resetCounters();
        $results = $this->_apiSubscribers->sendSubscribers($storeId, $listId);
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE, $storeId)) {
            $this->_helper->log('Generate Products payload');
            $products = $this->_apiProduct->_sendProducts($storeId);
            $countProducts = count($products);
            $results = array_merge($results, $products);

            $this->_helper->log('Generate Customers payload');
            $customers = $this->_apiCustomer->sendCustomers($storeId);
            $countCustomers = count($customers);
            $results = array_merge($results, $customers);

            $this->_helper->log('Generate Orders payload');
            $orders = $this->_apiOrder->sendOrders($storeId);
            $countOrders = count($orders);
            $results = array_merge($results, $orders);

            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC, $storeId)) {
                $this->_helper->log('Generate Carts payload');
                $carts = $this->_apiCart->createBatchJson($storeId);
                $results = array_merge($results, $carts);
            } else {
                $this->_helper->log('No Carts will be synced until the store is completely synced');
            }
            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_SEND_PROMO, $storeId)) {
                $this->_helper->log('Generate Rules payload');
                $rules = $this->_apiPromoRules->sendRules($storeId);
                $results = array_merge($results, $rules);

                $this->_helper->log('Generate Coupons payload');
                $coupons = $this->_apiPromoCodes->sendCoupons($storeId);
                $results = array_merge($results, $coupons);
            }
        }

        if (!empty($results)) {
            try {
                $batchArray['operations'] = $results;
                $batchJson = json_encode($batchArray);

                if (!$batchJson || $batchJson == '') {
                    $this->_helper->log('An empty operation was detected');
                } else {
                    $api = $this->_helper->getApi($storeId);
                    $batchResponse = $api->batchOperation->add($batchArray);
                    if (!isset($batchResponse['id'])) {
                        $this->_helper->log('error in the call to batch');
                    } else {
                        $this->_mailChimpSyncBatches->setStoreId($storeId);
                        $this->_mailChimpSyncBatches->setBatchId($batchResponse['id']);
                        $this->_mailChimpSyncBatches->setStatus($batchResponse['status']);
                        $this->_mailChimpSyncBatches->setMailchimpStoreId($mailchimpStoreId);
                        $this->_mailChimpSyncBatches->setModifiedDate($this->_helper->getGmtDate());
                        $this->_mailChimpSyncBatches->getResource()->save($this->_mailChimpSyncBatches);
                        $batchId = $batchResponse['id'];
                        $this->_showResume($batchId, $storeId);
                    }
                }
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            } catch (\Exception $e) {
                $this->_helper->log("Json encode fails");
                $this->_helper->log(var_export($batchArray, true));
            }
        } else {
            $this->_helper->log("Nothing to sync for store $storeId");
        }
        $countTotal = $countCustomers + $countProducts + $countOrders;
        $syncing = $this->_helper->getMCMinSyncing($storeId);
        if ($countTotal == 0 && $syncing) {
            $this->_helper->saveConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC, date('Y-m-d'), $storeId);
        }

        return $batchId;
    }

    /**
     * @param $storeId
     * @param $mailchimpStoreId
     */
    protected function updateSyncFlagData($storeId, $mailchimpStoreId)
    {
        $this->apiUpdateSyncFlag($storeId, $mailchimpStoreId);
        $this->_helper->saveConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC . "/$mailchimpStoreId", date('Y-m-d'), 0, 'default');
    }

    /**
     * @param $storeId
     * @param $mailchimpStoreId
     */
    protected function apiUpdateSyncFlag($storeId, $mailchimpStoreId)
    {
        try {
            $api = $this->_helper->getApi($storeId);
            $api->ecommerce->stores->edit($mailchimpStoreId, null, null, null, null, null, null, null, null, null, null, false);
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log('MailChimp error when updating syncing flag for store ' . $storeId);
            $this->_helper->log($e->getFriendlyMessage());
        }
    }
    protected function _ping($storeId)
    {
        try {
            $api = $this->_helper->getApi($storeId);
            $api->root->info();
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
            return false;
        }
        return true;
    }
    protected function _showResume($batchId, $storeId)
    {
        $this->_helper->log("Sent batch $batchId for store $storeId");
        $this->_helper->log($this->_helper->getCounters());
    }
}
