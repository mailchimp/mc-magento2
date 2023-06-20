<?php

namespace Ebizmarts\MailChimp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory;
use Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce;
use Ebizmarts\MailChimp\Model\MailChimpErrors;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Model\OrderFactory;

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
     * @param Context $context
     * @param MailChimpSyncEcommerceFactory $chimpSyncEcommerceFactory
     * @param MailChimpErrors $mailChimpErrors
     * @param MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        MailChimpSyncEcommerceFactory $chimpSyncEcommerceFactory,
        MailChimpErrors $mailChimpErrors,
        MailChimpSyncEcommerce $chimpSyncEcommerce,
        OrderFactory $orderFactory
    ) {
        $this->chimpSyncEcommerceFactory = $chimpSyncEcommerceFactory;
        $this->mailChimpErrors = $mailChimpErrors;
        $this->chimpSyncEcommerce = $chimpSyncEcommerce;
        $this->orderFactory = $orderFactory;
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
                if ($modified!==null) {
                    $chimpSyncEcommerce->setMailchimpSyncModified($modified);
                } else {
                    $chimpSyncEcommerce->setMailchimpSyncModified(0);
                }
                if ($date) {
                    $chimpSyncEcommerce->setMailchimpSyncDelta($date);
                } elseif ($modified != 1) {
                    $chimpSyncEcommerce->setBatchId(null);
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
            if ($type==\Ebizmarts\MailChimp\Helper\Data::IS_ORDER) {
                if ($sent||$error) {
                    $order = $this->orderFactory->create()->loadByAttribute('entity_id', $entityId);
                    if ($sent) {
                        $order->setMailchimpSent($sent);
                    }
                    if ($error) {
                        $order->setMailchimpSyncError($error);
                    }
                    $order->save();
                }
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
    public function resetErrors($mailchimpStore, $retry)
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
            }
        } catch (\Zend_Db_Exception $e) {
            throw new ValidatorException(__($e->getMessage()));
        }
    }
}
