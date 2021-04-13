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
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncBatchesFactory
     */
    private $_mailChimpSyncBatchesFactory;
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
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    private $_dir;

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
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatchesFactory $mailChimpSyncBatchesFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
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
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatchesFactory $mailChimpSyncBatchesFactory,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {

        $this->_storeManager    = $storeManager;
        $this->_helper          = $helper;
        $this->_apiProduct      = $apiProduct;
        $this->_mailChimpSyncBatchesFactory = $mailChimpSyncBatchesFactory;
        $this->_apiResult       = $apiResult;
        $this->_apiCustomer     = $apiCustomer;
        $this->_apiOrder        = $apiOrder;
        $this->_apiCart         = $apiCart;
        $this->_apiSubscribers  = $apiSubscriber;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_apiPromoCodes   = $apiPromoCodes;
        $this->_apiPromoRules   = $apiPromoRules;
        $this->_dir             = $dir;
    }

    public function execute()
    {

        $connection = $this->_chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->_chimpSyncEcommerce->getResource()->getMainTable();
        $connection->delete(
            $tableName,
            'batch_id is null and mailchimp_sync_modified != 1 and mailchimp_sync_error is null'
        );

        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
                if (!$this->_ping($storeId)) {
                    $this->_helper->log('MailChimp is not available');
                    return;
                }
                $this->_storeManager->setCurrentStore($storeId);
                $listId = $this->_helper->getGeneralList($storeId);
                $mailchimpStoreId = $this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                    $storeId
                );
                if ($mailchimpStoreId != -1 && $mailchimpStoreId != '') {
                    $this->_apiResult->processResponses($storeId, true, $mailchimpStoreId);
                    $batchId = $this->_processStore($storeId, $mailchimpStoreId, $listId);
                    if ($batchId) {
                        $connection->update(
                            $tableName,
                            [
                                'batch_id' => $batchId,
                                'mailchimp_sync_modified' => 0,
                                'mailchimp_sync_delta' => $this->_helper->getGmtDate()
                            ],
                            "batch_id is null and mailchimp_store_id = '$mailchimpStoreId' and mailchimp_sync_error is null"
                        );
                        $connection->update(
                            $tableName,
                            [
                                'batch_id' => $batchId,
                                'mailchimp_sync_modified' => 0,
                                'mailchimp_sync_delta' => $this->_helper->getGmtDate()],
                            "batch_id is null and mailchimp_store_id = '$listId' and mailchimp_sync_error is null"
                        );
                    }
                }
            }
        }
        $syncs = [];
        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            $mailchimpStoreId = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $storeId
            );
            if ($mailchimpStoreId != -1 && $mailchimpStoreId != '') {
                $dateSync = $this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC,
                    $storeId
                );
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
            if ($val && !$this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC . "/$mailchimpStoreId",
                0,
                'default'
            )
            ) {
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
            list($OKOperations, $BadOperations) = $this->encodeOperations($results);
            $batchArray['operations'] = $OKOperations;
            try {

                if (!count($batchArray['operations'])) {
                    $this->_helper->log('An empty operation was detected');
                } else {
                    $api = $this->_helper->getApi($storeId);
                    $batchResponse = $api->batchOperation->add($batchArray);
                    if (!isset($batchResponse['id'])) {
                        $this->_helper->log('error in the call to batch');
                    } else {
                        $syncBatches = $this->_mailChimpSyncBatchesFactory->create();
                        $syncBatches->setStoreId($storeId);
                        $syncBatches->setBatchId($batchResponse['id']);
                        $syncBatches->setStatus(\Ebizmarts\MailChimp\Helper\Data::BATCH_PENDING);
                        $syncBatches->setMailchimpStoreId($mailchimpStoreId);
                        $syncBatches->setModifiedDate($this->_helper->getGmtDate());
                        $syncBatches->getResource()->save($syncBatches);
                        $batchId = $batchResponse['id'];
                        $this->_showResume($batchId, $storeId);
                    }
                }
                if (count($BadOperations)) {
                    $this->markWithError($BadOperations, $mailchimpStoreId, $listId);
                }

            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        } else {
            $this->_helper->log("Nothing to sync for store $storeId");
        }
        $countTotal = $this->_helper->getTotalNewItemsSent();
        $syncing = $this->_helper->getMCMinSyncing($storeId);
        if ($countTotal == 0 && $syncing) {
            $this->_helper->saveConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC,
                date('Y-m-d'),
                $storeId
            );
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
        $this->_helper->saveConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC . "/$mailchimpStoreId",
            date('Y-m-d'),
            0,
            'default'
        );
    }

    /**
     * @param $storeId
     * @param $mailchimpStoreId
     */
    protected function apiUpdateSyncFlag($storeId, $mailchimpStoreId)
    {
        try {
            $api = $this->_helper->getApi($storeId);
            $api->ecommerce->stores->edit(
                $mailchimpStoreId,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                false
            );
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

    protected function _saveRequest($resquest)
    {
        $pathLog = $this->_dir->getPath('log').DIRECTORY_SEPARATOR.'Request'.$this->_helper->getGmtTimeStamp().'.log';
        error_log(var_export($resquest, true), 3, $pathLog);
        $this->_helper->log("Request with error was saved in $pathLog");
    }

    protected function encodeOperations($operations)
    {
        $OKOperations = [];
        $BadOperations = [];
        $batchJson = json_encode($operations);
        $jsonLastErrorGeneral  = json_last_error();
        $jsonLastErrorMsgGeneral = json_last_error_msg();
        if ($jsonLastErrorGeneral) {
            $this->_helper->log("Encode error");
            foreach ($operations as $opIndex => $operation) {
                $jsonEncode = json_encode($operation);
                $jsonLastErrorItem = json_last_error();
                if ($jsonLastErrorItem) {
                    $jsonLastErrorMsgItem = json_last_error_msg();
                    $this->_helper->log("");
                    $this->_helper->log("json_encode error: $jsonLastErrorMsgItem, operation:");
                    $this->_saveRequest($operation);
                    /*remove failing operation*/
                    $BadOperations[] = $operation;
                    unset($operations[$opIndex]);
                } else {
                    $OKOperations[] = $operation;
                }
            }
        } else {
            $OKOperations = $operations;
        }
        return [$OKOperations, $BadOperations];
    }
    protected function markWithError($operations, $mailchimpStoreId, $listId)
    {
        $type = null;
        $relatedId = null;
        $types = [
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
            \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT,
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_CODE,
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE,
            \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER,
            \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER
        ];
        $connection = $this->_chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->_chimpSyncEcommerce->getResource()->getMainTable();
        foreach ($operations as $operation) {
            if (is_array($operation)) {
                if (array_key_exists('operation_id', $operation)) {
                    $operationId = explode("_", $operation['operation_id']);
                    if (isset($operationId[0])) {
                        $type = $operationId[0];
                        if (!in_array($type, $types)) {
                            $type = '';
                        } else {
                            if ($type == \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER) {
                                $storeId = $listId;
                            } else {
                                $storeId = $mailchimpStoreId;
                            }
                        }
                    }
                    if (isset($operationId[2])) {
                        $relatedId = $operationId[2];
                    }
                    if ($type && $relatedId) {
                        $connection->update(
                            $tableName,
                            [
                            'batch_id' => -1,
                            'mailchimp_sync_modified' => 0,
                            'mailchimp_sync_delta' => $this->_helper->getGmtDate(),
                            'mailchimp_sync_error' => __('Json error'),
                            'mailchimp_sent' => \Ebizmarts\MailChimp\Helper\Data::NOTSYNCED
                            ],
                            "batch_id is null and mailchimp_store_id = '$storeId' and type ='$type' and related_id = $relatedId"
                        );
                    }
                }
            }
        }
    }
}
