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
    private $_webhook;
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
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollection
     * @param \Magento\Customer\Model\CustomerFactory $customer
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
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce,
        \Ebizmarts\MailChimp\Cron\Webhook $webhook
    )
    {
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
        $this->_webhook             = $webhook;
    }

    public function execute()
    {
        $connection = $this->_chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->_chimpSyncEcommerce->getResource()->getMainTable();
        $connection->delete($tableName, 'batch_id is null');

        foreach($this->_storeManager->getStores() as $storeId => $val)
        {
            $this->_storeManager->setCurrentStore($storeId);
            $listId = $this->_helper->getGeneralList($storeId);
            if($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE)) {
                $mailchimpStoreId  = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,$storeId);
                if ($mailchimpStoreId != -1)
                {
                    $this->_apiResult->processResponses($storeId,true, $mailchimpStoreId);
                    $batchId =$this->_processStore($storeId, $mailchimpStoreId);
                    if ($batchId) {
                        $connection->update($tableName, ['batch_id' => $batchId], "batch_id is null and mailchimp_store_id = '$mailchimpStoreId'");
                        $connection->update($tableName, ['batch_id' => $batchId], "batch_id is null and mailchimp_store_id = '$listId'");
                    }
                }
            }
        }
        $this->_webhook->processWebhooks();

    }

    protected function _processStore($storeId, $mailchimpStoreId)
    {
        $batchId = null;
        $batchArray = array();
        $results = array();
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE,$storeId)) {
            $results = $this->_apiSubscribers->sendSubscribers($storeId);
            $products =  $this->_apiProduct->_sendProducts($storeId);
            $results = array_merge($results, $products);
            $customers = $this->_apiCustomer->sendCustomers($storeId);
            $results = array_merge($results,$customers);
            $orders = $this->_apiOrder->sendOrders($storeId);
            $results = array_merge($results,$orders);
            $carts = $this->_apiCart->createBatchJson($storeId);
            $results= array_merge($results,$carts);
        }
        if (!empty($results)) {
            try {
                $batchArray['operations'] = $results;
                $batchJson = json_encode($batchArray);

                if (!$batchJson || $batchJson == '') {
                    $this->_helper->log('An empty operation was detected');
                } else {
                    $api = $this->_helper->getApi();
                    $batchResponse =$api->batchOperation->add($batchArray);
                    $this->_helper->log($results,null,$batchResponse['id']);
                    $this->_helper->log(var_export($batchResponse,true));
                    $this->_mailChimpSyncBatches->setStoreId($storeId);
                    $this->_mailChimpSyncBatches->setBatchId($batchResponse['id']);
                    $this->_mailChimpSyncBatches->setStatus($batchResponse['status']);
                    $this->_mailChimpSyncBatches->setMailchimpStoreId($mailchimpStoreId);
                    $this->_mailChimpSyncBatches->getResource()->save($this->_mailChimpSyncBatches);
                    $batchId = $batchResponse['id'];
                }
            } catch(Exception $e) {
                $this->_helper->log("Json encode fails");
                $this->_helper->log(var_export($batchArray,true));
            }
        }
        return $batchId;
    }

}