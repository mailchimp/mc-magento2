<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpErrors extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpErrors::class);
    }

    public function getByStoreIdType($storeId, $id, $type)
    {
        $this->getResource()->getByStoreIdType($this, $storeId, $id, $type);

        return $this;
    }

    public function deleteByStorePeriod($storeId, $interval, $limit)
    {
        return $this->getResource()->deleteByStorePeriod($this, $storeId, $interval, $limit);
    }
}
