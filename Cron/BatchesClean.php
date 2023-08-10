<?php

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
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
    ) {
        $this->helper = $helper;
        $this->mailChimpSyncBatches = $mailChimpSyncBatches;
        $this->mailChimpErrors = $chimpErrors;
    }

    public function execute()
    {
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

        try {
            $connection = $this->mailChimpSyncBatches->getResource()->getConnection();
            $tableName = $this->mailChimpSyncBatches->getResource()->getMainTable();
            $select = $connection->select();
            $select->from($tableName, ['batch_id']);
            $existingBatchIds = $connection->fetchCol($select);
            $connection = $this->mailChimpErrors->getResource()->getConnection();
            $tableName = $this->mailChimpErrors->getResource()->getMainTable();
            if ($existingBatchIds) {
                $connection->delete($tableName, [
                    'batch_id NOT IN (?)' => $existingBatchIds,
                ]);
            } else {
                $connection->delete($tableName);
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
}
