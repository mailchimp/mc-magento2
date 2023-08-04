<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpErrors extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_errors', 'id');
    }

    public function getByStoreIdType(\Ebizmarts\MailChimp\Model\MailChimpErrors $errors, $storeId, $id, $type)
    {
        $connection = $this->getConnection();
        $bind = ['store_id' => $storeId, 'regtype' => $type, 'original_id' => $id];
        $select = $connection->select()->from(
            $this->getTable('mailchimp_errors')
        )->where(
            'store_id = :store_id AND regtype = :regtype AND original_id = :original_id'
        );
        $data = $connection->fetchRow($select, $bind);
        if ($data) {
            $errors->setData($data);
        }

        return $errors;
    }

    public function deleteByStorePeriod(\Ebizmarts\MailChimp\Model\MailChimpErrors $errors, $storeId, $interval, $limit)
    {
        $connection = $this->getConnection();
        $table = $this->getTable('mailchimp_errors');
        $ret = $connection->query(
            "DELETE FROM $table WHERE date_add(added_at, interval $interval month) < now() AND store_id = $storeId LIMIT $limit"
        );

        return $ret;
    }
}
