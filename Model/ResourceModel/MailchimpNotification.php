<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @deprecated The mailchimp_notification table is dropped and nothing reads or
 *             writes it. Kept only because Helper\Data still takes
 *             MailchimpNotificationFactory in its constructor.
 */
class MailchimpNotification extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mailchimp_notification', 'id');
    }

}
