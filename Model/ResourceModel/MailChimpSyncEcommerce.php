<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpSyncEcommerce extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_sync_ecommerce', 'id');
    }

    public function getByStoreIdType(\Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp, $storeId, $id, $type)
    {
        $connection = $this->getConnection();
        $bind = ['store_id' => $storeId, 'type' => $type, 'related_id' => $id];
        $select = $connection->select()->from(
            $this->getTable('mailchimp_sync_ecommerce')
        )->where(
            'mailchimp_store_id = :store_id AND type = :type AND related_id = :related_id'
        );
        $data = $connection->fetchRow($select, $bind);
        if ($data) {
            $chimp->setData($data);
        }

        return $chimp;
    }

    public function markAllAsDeleted(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp,
        $id,
        $type,
        $relatedDeletedId
    ) {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('mailchimp_sync_ecommerce'),
            ['mailchimp_sync_deleted' => 1, 'deleted_related_id' => $relatedDeletedId],
            ['related_id = ?' => $id, 'type = ?' => $type]
        );

        return $this;
    }

    public function markAllAsModified(\Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp, $id, $type)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('mailchimp_sync_ecommerce'),
            [
                'mailchimp_sync_modified' => 1,
                'mailchimp_sent' => \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC,
                'batch_id' => null
            ],
            ['related_id = ?' => $id, 'type = ?' => $type]
        );

        return $this;
    }

    public function deleteAllByIdType(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp,
        $id,
        $type,
        $mailchimpStoreId
    ) {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('mailchimp_sync_ecommerce'),
            ['related_id = ?' => $id, 'type = ?' => $type, 'mailchimp_store_id = ?' => $mailchimpStoreId]
        );

        return $this;
    }

    public function deleteAllByBatchId(\Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp, $batchId)
    {
        $connection = $this->getConnection();
        $connection->delete($this->getTable('mailchimp_sync_ecommerce'), ['batch_id = ?' => $batchId]);

        return $this;
    }

    public function markAllAsModifiedByIds(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimp,
        $mailchimpStore,
        $ids,
        $type
    ) {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('mailchimp_sync_ecommerce'),
            ['mailchimp_sync_modified' => 1],
            ['related_id in (?)' => $ids, 'type = ?' => $type, 'mailchimp_store_id = ?' => $mailchimpStore]
        );

        return $this;
    }
}
