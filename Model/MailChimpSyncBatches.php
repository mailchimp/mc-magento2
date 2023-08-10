<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpSyncBatches extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches::class);
    }
}
