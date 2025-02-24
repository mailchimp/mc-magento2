<?php

namespace Ebizmarts\MailChimp\Model;

class MailchimpNotification extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification::class);
    }
}