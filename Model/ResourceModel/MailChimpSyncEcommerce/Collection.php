<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class
        );
    }
}
