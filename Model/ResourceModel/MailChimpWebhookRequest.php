<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MailChimpWebhookRequest extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_webhook_request', 'id');
    }
}
