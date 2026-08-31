<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/17/16 1:57 PM
 * @file: MailChimpError.php
 */
namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\DB\Select;
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
    /**
     * Drop the oldest rows for a store once it holds more than $keep of them.
     *
     * A ceiling, not a preference. `clean_errors_months` answers how long a
     * merchant wants errors kept, and keeping them forever is a legitimate
     * answer; this answers a different question — that whatever they chose,
     * the table does not grow without bound. Both can hold at once, which is
     * why neither has to guess what the other meant.
     *
     * Deletes at most $limit rows per call. The caller repeats until the store
     * is under the ceiling or its time budget is spent, so each statement holds
     * its locks briefly and no single one grows with the size of the table.
     *
     * @param  \Ebizmarts\MailChimp\Model\MailChimpErrors $errors
     * @param  int                                        $storeId
     * @param  int                                        $keep    newest rows to leave in place
     * @param  int                                        $limit   most rows to delete in one run
     * @return int rows deleted
     */
    public function deleteOverflowByStore(\Ebizmarts\MailChimp\Model\MailChimpErrors $errors, $storeId, $keep, $limit)
    {
        $connection = $this->getConnection();
        $table      = $this->getTable('mailchimp_errors');

        // The id of the first row past the ceiling. Absent means the store is
        // under it and there is nothing to do.
        //
        // Served by MAILCHIMP_ERRORS_STORE_ID_ID. Without it the existing
        // index cannot order by id within a store, so the optimiser walks the
        // primary key backwards through every other store view's rows to reach
        // this one's.
        $oldest = $connection->fetchOne(
            $connection->select()
                ->from($table, 'id')
                ->where('store_id = ?', (int)$storeId)
                ->order('id DESC')
                ->limit(1, (int)$keep)
        );

        if (!$oldest) {
            return 0;
        }

        // Raw because the adapter's delete() takes no LIMIT, and the bound is
        // the point. Identifier comes from getTable(), values are bound, and
        // the limit is cast.
        $result = $connection->query(
            'DELETE FROM ' . $table . ' WHERE store_id = ? AND id <= ? ORDER BY id ASC LIMIT ' . (int)$limit,
            [(int)$storeId, (int)$oldest]
        );

        return $result->rowCount();
    }

    public function deleteByStorePeriod(\Ebizmarts\MailChimp\Model\MailChimpErrors $errors, $storeId, $interval, $limit)
    {
        $connection = $this->getConnection();
        $table = $this->getTable('mailchimp_errors');
        $ret = $connection->query("DELETE FROM $table WHERE date_add(added_at, interval $interval month) < now() AND store_id = $storeId LIMIT $limit");
        return $ret;
    }
}
