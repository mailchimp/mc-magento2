<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/21/16 4:55 PM
 * @file: MailChimpSyncEcommerce.php
 */

namespace Ebizmarts\MailChimp\Model;

class MailChimpSyncEcommerce extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce
     */
    protected $mailChimpSyncEcommerceResource;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce $mailChimpSyncEcommerceResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mailChimpSyncEcommerceResource = $mailChimpSyncEcommerceResource;
        parent::__construct($context, $registry, $resource ?? $mailChimpSyncEcommerceResource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class);
    }

    public function getByStoreIdType($storeId, $id, $type)
    {
        $this->mailChimpSyncEcommerceResource->getByStoreIdType($this, $storeId, $id, $type);
        return $this;
    }
    public function markAllAsDeleted($id, $type, $relatedDeletedId)
    {
        $this->mailChimpSyncEcommerceResource->markAllAsDeleted($this, $id, $type, $relatedDeletedId);
        return $this;
    }
    public function markEcommorceAsNotDeleted($id, $type)
    {
        $this->mailChimpSyncEcommerceResource->markEcommorceAsNotDeleted($this, $id, $type);
        return $this;
    }
    public function markAllAsModified($id, $type)
    {
        $this->mailChimpSyncEcommerceResource->markAllAsModified($this, $id, $type);
        return $this;
    }
    public function deleteAllByIdType($id, $type, $mailchimpStoreId)
    {
        $this->mailChimpSyncEcommerceResource->deleteAllByIdType($this, $id, $type, $mailchimpStoreId);
        return $this;
    }
    public function markAllAsModifiedByBatchId($batchId)
    {
        $this->mailChimpSyncEcommerceResource->markAllAsModifiedByBatchId($this, $batchId);
        return $this;
    }
    public function deleteAllByBatchid($batchId)
    {
        $this->mailChimpSyncEcommerceResource->deleteAllByBatchid($this, $batchId);
    }
    public function deleteByTypeAndId($type, $id, $mailchimpStoreId)
    {
        $this->mailChimpSyncEcommerceResource->deleteByTypeAndId($this, $type, $id, $mailchimpStoreId);
    }
    public function markAllAsModifiedByIds($mailchimpStoreId, $ids, $type)
    {
        $this->mailChimpSyncEcommerceResource->markAllAsModifiedByIds($this, $mailchimpStoreId, $ids, $type);
    }
}
