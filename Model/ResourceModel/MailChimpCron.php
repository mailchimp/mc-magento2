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

class MailChimpCron extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('cron_schedule', 'schedule_id');
    }

    /**
     * @param \Ebizmarts\MailChimp\Model\MailChimpCron $cron
     * @return \Ebizmarts\MailChimp\Model\MailChimpCron
     */
    public function getCronList(\Ebizmarts\MailChimp\Model\MailChimpCron $cron)
    {
        $connection = $this->getConnection();
        $tableName = $connection->getTableName('cron_schedule');
        $select = $connection->select()->from($tableName)
            ->where('job_code = ?', 'ebizmarts_ecommerce')
            ->orWhere('job_code = ?', 'ebizmarts_webhooks');
        $data = $connection->fetchAll($select);
        if ($data) {
            $cron->setData($data);
        }
        return $cron;
    }
}
