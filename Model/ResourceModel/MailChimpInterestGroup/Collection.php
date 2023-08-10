<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpInterestGroup::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup::class
        );
    }
}
