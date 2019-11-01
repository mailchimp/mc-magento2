<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/20/17 3:51 PM
 * @file: MailChimpInterestGroup.php
 */

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpInterestGroup extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_interest_group', 'id');
    }
    public function getBySubscriberIdStoreId(
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroup $mailChimpInterestGroup,
        $subscriberId,
        $storeId
    ) {
        $connection = $this->getConnection();
        $bind = ['subscriber_id'=>$subscriberId, 'store_id' => $storeId];
        $select = $connection->select()->from(
            $this->getTable('mailchimp_interest_group')
        )->where(
            'subscriber_id = :subscriber_id AND store_id = :store_id'
        );
        $data = $connection->fetchRow($select, $bind);
        if ($data) {
            $mailChimpInterestGroup->setData($data);
        }
        return $mailChimpInterestGroup;
    }
}
