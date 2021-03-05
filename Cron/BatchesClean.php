<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 22/11/18 10:02 AM
 * @file: BatchesClean.php
 */
namespace Ebizmarts\MailChimp\Cron;

class BatchesClean
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
     */
    protected $mailChimpSyncBatches;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrors
     */
    protected $mailChimpErrors;

    /**
     * BatchesClean constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
    ) {
        $this->helper               = $helper;
        $this->mailChimpSyncBatches = $mailChimpSyncBatches;
        $this->mailChimpErrors      = $chimpErrors;
    }
    public function execute()
    {

        try {
            $connection = $this->mailChimpErrors->getResource()->getConnection();
            $tableName  = $this->mailChimpErrors->getResource()->getMainTable();
            $tableNameBatches = $this->mailChimpSyncBatches->getResource()->getMainTable();
            $quoteInto = $connection->quoteInto(
                "batch_id IN (SELECT batch_id FROM $tableNameBatches WHERE status IN('completed','canceled') AND ( date_add(modified_date, interval ? month) < now() OR modified_date IS NULL)) OR batch_id NOT IN(SELECT batch_id FROM $tableNameBatches)",
                1
            );
            $connection->delete($tableName, $quoteInto);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
        try {
            $connection = $this->mailChimpSyncBatches->getResource()->getConnection();
            $tableName = $this->mailChimpSyncBatches->getResource()->getMainTable();
            $quoteInto = $connection->quoteInto(
                'status IN("completed","canceled") and ( date_add(modified_date, interval ? month) < now() OR modified_date IS NULL)',
                1
            );
            $connection->delete($tableName, $quoteInto);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
}
