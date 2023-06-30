<?php

namespace Ebizmarts\MailChimp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory;
use Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce;
use Ebizmarts\MailChimp\Model\MailChimpErrors;

class Sync extends AbstractHelper
{
    /**
     * @var MailChimpSyncEcommerceFactory
     */
    private $chimpSyncEcommerceFactory;
    /**
     * @var MailChimpErrors
     */
    private $mailChimpErrors;
    /**
     * @var MailChimpSyncEcommerce
     */
    private $chimpSyncEcommerce;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param Context $context
     * @param MailChimpSyncEcommerceFactory $chimpSyncEcommerceFactory
     * @param MailChimpErrors $mailChimpErrors
     * @param MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param OrderFactory $orderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        Context $context,
        MailChimpSyncEcommerceFactory $chimpSyncEcommerceFactory,
        MailChimpErrors $mailChimpErrors,
        MailChimpSyncEcommerce $chimpSyncEcommerce,
        OrderFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ProductFactory $productFactory
    ) {
        $this->chimpSyncEcommerceFactory = $chimpSyncEcommerceFactory;
        $this->mailChimpErrors = $mailChimpErrors;
        $this->chimpSyncEcommerce = $chimpSyncEcommerce;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }
    public function saveEcommerceData(
        $storeId,
        $entityId,
        $type,
        $date = null,
        $error = null,
        $modified = null,
        $deleted = null,
        $token = null,
        $sent = null
    ) {
        if (!empty($entityId)) {
            $chimpSyncEcommerce = $this->getChimpSyncEcommerce($storeId, $entityId, $type);
            if ($chimpSyncEcommerce->getRelatedId() == $entityId ||
                !$chimpSyncEcommerce->getRelatedId() && $modified != 1) {
                $chimpSyncEcommerce->setMailchimpStoreId($storeId);
                $chimpSyncEcommerce->setType($type);
                $chimpSyncEcommerce->setRelatedId($entityId);
                if ($modified !== null) {
                    $chimpSyncEcommerce->setMailchimpSyncModified($modified);
                    $chimpSyncEcommerce->setBatchId(null);
                } else {
                    $chimpSyncEcommerce->setMailchimpSyncModified(0);
                }
                if ($date) {
                    $chimpSyncEcommerce->setMailchimpSyncDelta($date);
                }
                if ($error) {
                    $chimpSyncEcommerce->setMailchimpSyncError($error);
                }
                if ($deleted) {
                    $chimpSyncEcommerce->setMailchimpSyncDeleted($deleted);
                    $chimpSyncEcommerce->setMailchimpSyncModified(0);
                }
                if ($token) {
                    $chimpSyncEcommerce->setMailchimpToken($token);
                }
                if ($sent) {
                    $chimpSyncEcommerce->setMailchimpSent($sent);
                }
                $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);
            }
            switch ($type) {
                case \Ebizmarts\MailChimp\Helper\Data::IS_ORDER :
                    if ($sent || $error) {
                        $order = $this->orderFactory->create()->loadByAttribute('entity_id', $entityId);
                        if ($sent) {
                            $order->setMailchimpSent($sent);
                        }
                        if ($error) {
                            $order->setMailchimpSyncError($error);
                        }
                        $order->save();
                    }
                    break;
                case \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT :
                    if ($sent || $error) {
                        $product = $this->productFactory->create()->load($entityId);
                        $product->setSync(true);
                        if ($sent) {
                            $product->setMailchimpSent($sent);
                        }
                        if ($error) {
                            $product->setMailchimpSyncError($error);
                        }
                        $product->save();
                    }
                    break;
            }
        }
    }
    public function getChimpSyncEcommerce($storeId, $id, $type)
    {
        $chimp = $this->chimpSyncEcommerceFactory->create();
        return $chimp->getByStoreIdType($storeId, $id, $type);
    }
    public function markEcommerceAsDeleted($relatedId, $type, $relatedDeletedId = null)
    {
        $this->chimpSyncEcommerce->markAllAsDeleted($relatedId, $type, $relatedDeletedId);
    }
    public function ecommerceDeleteAllByIdType($id, $type, $mailchimpStoreId)
    {
        $this->chimpSyncEcommerce->deleteAllByIdType($id, $type, $mailchimpStoreId);
    }
    public function deleteAllByBatchId($batchId)
    {
        $this->chimpSyncEcommerce->deleteAllByBatchid($batchId);
    }
    public function markRegisterAsModified($registerId, $type)
    {
        if (!empty($registerId)) {
            $this->chimpSyncEcommerce->markAllAsModified($registerId, $type);
        }
    }
    public function markAllAsModifiedByIds($mailchimpStoreId, $ids, $type)
    {
        $this->chimpSyncEcommerce->markAllAsModifiedByIds($mailchimpStoreId, $ids, $type);
    }
    public function resyncAllSubscribers($mailchimpList)
    {
        $connection = $this->chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->chimpSyncEcommerce->getResource()->getMainTable();
        $connection->update(
            $tableName,
            ['mailchimp_sync_modified' => 1],
            "type = '" . \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER . "' and mailchimp_store_id = '$mailchimpList'"
        );
    }
    public function resyncProducts($mailchimpList)
    {
        $connection = $this->chimpSyncEcommerce->getResource()->getConnection();
        $tableName = $this->chimpSyncEcommerce->getResource()->getMainTable();
        $connection->update(
            $tableName,
            ['mailchimp_sync_modified' => 1],
            "type = '" . \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT . "' and mailchimp_store_id = '$mailchimpList'"
        );
    }

    public function resetErrors($mailchimpStore, $storeId, $retry)
    {
        try {
            // clean the errors table
            $connection = $this->mailChimpErrors->getResource()->getConnection();
            $tableName = $this->mailChimpErrors->getResource()->getMainTable();
            $connection->delete($tableName, "mailchimp_store_id = '".$mailchimpStore."'");
            // clean the syncecommerce table with errors
            if ($retry) {
                $connection = $this->chimpSyncEcommerce->getResource()->getConnection();
                $tableName = $this->chimpSyncEcommerce->getResource()->getMainTable();
                $connection->delete(
                    $tableName,
                    "mailchimp_store_id = '" . $mailchimpStore . "' and mailchimp_sync_error is not null"
                );
                // clean the order table
                $orderCollection = $this->orderCollectionFactory->create();
                $orderCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
                $orderCollection->addFieldToFilter('mailchimp_sync_error', ['notnull' => true]);
                foreach ($orderCollection as $item) {
                    $item->setMailchimpSent(\Ebizmarts\MailChimp\Helper\Data::NEVERSYNC);
                    $item->setMailchimpSyncError('');
                    $item->save();
                }
            }
        } catch (\Zend_Db_Exception $e) {
            throw new ValidatorException(__($e->getMessage()));
        }
    }
}
