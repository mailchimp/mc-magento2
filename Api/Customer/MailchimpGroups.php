<?php

namespace Ebizmarts\MailChimp\Api\Customer;

interface MailchimpGroups extends \Magento\Customer\Api\Data\CustomerInterface
{
    const MAILCHIMP_GROUPS = 'mailchimp_groups';
    public function getMailchimpGroups();
    public function setMailchimpGroups($groups);
}
