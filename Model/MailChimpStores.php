<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpStores extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpStores::class);
    }
}
