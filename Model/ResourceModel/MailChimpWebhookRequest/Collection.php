<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpWebhookRequest::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest::class
        );
    }
}
