<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpInterestGroup extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup::class);
    }

    public function getBySubscriberIdStoreId($subscriberId, $storeId)
    {
        $this->getResource()->getBySubscriberIdStoreId($this, $subscriberId, $storeId);

        return $this;
    }
}
