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
}
