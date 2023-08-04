<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpStores extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_stores', 'id');
    }
}
