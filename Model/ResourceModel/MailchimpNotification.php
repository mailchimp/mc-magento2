<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailchimpNotification extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_notification', 'id');
    }

}
