<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpSyncBatches extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_sync_batches', 'id');
    }
}
