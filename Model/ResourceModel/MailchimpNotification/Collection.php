<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification;

/**
 * @deprecated The mailchimp_notification table is dropped and nothing reads or
 *             writes it. Kept only because Helper\Data still takes
 *             MailchimpNotificationFactory in its constructor.
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailchimpNotification::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailchimpNotification::class
        );
    }
}
