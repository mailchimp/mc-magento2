<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpSyncEcommerce extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class);
    }

    public function getByStoreIdType($storeId, $id, $type)
    {
        $this->getResource()->getByStoreIdType($this, $storeId, $id, $type);

        return $this;
    }

    public function markAllAsDeleted($id, $type, $relatedDeletedId)
    {
        $this->getResource()->markAllAsDeleted($this, $id, $type, $relatedDeletedId);

        return $this;
    }

    public function markAllAsModified($id, $type)
    {
        $this->getResource()->markAllAsModified($this, $id, $type);

        return $this;
    }

    public function deleteAllByIdType($id, $type, $mailchimpStoreId)
    {
        $this->getResource()->deleteAllByIdType($this, $id, $type, $mailchimpStoreId);

        return $this;
    }

    public function deleteAllByBatchid($batchId)
    {
        $this->getResource()->deleteAllByBatchid($this, $batchId);
    }

    public function markAllAsModifiedByIds($mailchimpStoreId, $ids, $type)
    {
        $this->getResource()->markAllAsModifiedByIds($this, $mailchimpStoreId, $ids, $type);
    }
}
