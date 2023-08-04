<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpSyncBatches::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches::class
        );
    }
}
