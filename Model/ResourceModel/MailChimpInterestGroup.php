<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

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
        $bind = ['subscriber_id' => $subscriberId, 'store_id' => $storeId];
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
