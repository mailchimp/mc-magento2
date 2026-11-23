<?php

namespace Ebizmarts\MailChimp\Model;

/**
 * @deprecated The mailchimp_notification table is dropped and nothing reads or
 *             writes it. Kept only because Helper\Data still takes
 *             MailchimpNotificationFactory in its constructor.
 */
class MailchimpNotification extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification::class);
    }
}
