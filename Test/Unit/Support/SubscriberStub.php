<?php

namespace Ebizmarts\MailChimp\Test\Unit\Support;

abstract class SubscriberStub extends \Magento\Newsletter\Model\Subscriber
{
    abstract public function getSubscriberId();

    abstract public function getStoreId();
}
