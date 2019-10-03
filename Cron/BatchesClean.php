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
     * BatchesClean constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $_mailChimpSyncBatches
    ) {
        $this->helper               = $helper;
        $this->mailChimpSyncBatches = $_mailChimpSyncBatches;
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
    }
}
