<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\ResourceConnection;

/**
 * Local metadata that tells the service whether a store view is alive and whether
 * it is failing. Two questions, two indexed reads.
 *
 * SHOPPER DATA NEVER LEAVES THE STORE, BY CONSTRUCTION.
 * `mailchimp_errors.errors` holds the raw Mailchimp error body and
 * `mailchimp_notification.notification_data` holds request params and
 * responses; both can carry customer details. Neither is selected here. Only
 * the surrounding facts travel: when, and what kind.
 */
class LivenessSignals
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @param ResourceConnection $resource
     * @param MailChimpHelper    $helper
     */
    public function __construct(
        ResourceConnection $resource,
        MailChimpHelper $helper
    ) {
        $this->resource = $resource;
        $this->helper   = $helper;
    }

    /**
     * Build the signal block for one store view.
     *
     * Null handling is deliberate and asymmetric, because the service treats an
     * explicit null as "delete this field" and an absent key as "keep what you
     * have":
     *
     *  - the error fields are ALWAYS present, null when there is no error row,
     *    so a recovered store actively clears its old failure on the service;
     *  - last_sync_at is OMITTED when it cannot be read, so a transient local
     *    problem never erases a good value the service already holds.
     *
     * @param  int $storeId
     * @return array
     */
    public function forStore($storeId)
    {
        $signals = [];

        $lastSync = $this->getLastSyncDelta($storeId);
        if ($lastSync !== null) {
            $signals['last_sync_at'] = $lastSync;
        }

        $error = $this->getLastError($storeId);
        $signals['last_error_type']   = $error === null ? null : $error['type'];
        $signals['last_error_status'] = $error === null ? null : $error['status'];
        $signals['last_error_at']     = $error === null ? null : $error['added_at'];

        return $signals;
    }

    /**
     * Newest sync delta for the store view's Mailchimp store.
     *
     * `mailchimp_sync_delta` is indexed, so MAX() is a single seek. The table
     * is scoped by mailchimp_store_id, NOT store_id, so the store view's
     * configured Mailchimp store is what selects the rows.
     *
     * @param  int $storeId
     * @return string|null
     */
    private function getLastSyncDelta($storeId)
    {
        $mailchimpStoreId = $this->helper->getConfigValue(
            MailChimpHelper::XML_MAILCHIMP_STORE,
            $storeId
        );

        if (!$mailchimpStoreId) {
            return null;
        }

        try {
            $connection = $this->resource->getConnection();
            $select     = $connection->select()
                ->from($this->resource->getTableName('mailchimp_sync_ecommerce'), ['MAX(mailchimp_sync_delta)'])
                ->where('mailchimp_store_id = ?', $mailchimpStoreId);

            $value = $connection->fetchOne($select);
        } catch (\Throwable $e) {
            $this->helper->log('Edge beacon could not read the sync delta: ' . $e->getMessage());
            return null;
        }

        return $value ?: null;
    }

    /**
     * Newest error row for the store view.
     *
     * Selects only type, status and added_at. The `errors` column is never
     * read.
     *
     * This is not cheap on its own, and the earlier claim that it was — a
     * composite index leading on store_id, and few rows — was wrong on both
     * counts. That index continues into regtype and original_id, so it cannot
     * serve `ORDER BY id DESC`, and the optimiser walks the primary key
     * backwards through every other store view's rows instead: measured at
     * 52 ms on MariaDB 10.6 against a 250,000 row table.
     *
     * What makes it cheap is `MAILCHIMP_ERRORS_STORE_ID_ID`, which serves the
     * sort directly — 0.04 ms on the same table — and the row ceiling that
     * bounds how large the table gets. Both are in place. This runs hourly per
     * store view, so neither is optional.
     *
     * @param  int $storeId
     * @return array|null
     */
    private function getLastError($storeId)
    {
        try {
            $connection = $this->resource->getConnection();
            $select     = $connection->select()
                ->from($this->resource->getTableName('mailchimp_errors'), ['type', 'status', 'added_at'])
                ->where('store_id = ?', $storeId)
                ->order('id DESC')
                ->limit(1);

            $row = $connection->fetchRow($select);
        } catch (\Throwable $e) {
            $this->helper->log('Edge beacon could not read the last error: ' . $e->getMessage());
            return null;
        }

        if (!is_array($row) || empty($row)) {
            return null;
        }

        return [
            'type'     => $row['type'] !== null ? (string)$row['type'] : null,
            'status'   => $row['status'] !== null ? (string)$row['status'] : null,
            'added_at' => $row['added_at'] !== null ? (string)$row['added_at'] : null,
        ];
    }
}
