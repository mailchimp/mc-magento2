<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\Newsletter;

class Collection extends \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->showCustomerInfo(true)->addSubscriberTypeField()->showStoreInfo();
        $this->_map['fields']['phone'] = 'main_table.phone';
        return $this;
    }
}
