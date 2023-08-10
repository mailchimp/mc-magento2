<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpWebhookRequest extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest::class);
    }
}
