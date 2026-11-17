<?php

namespace Ebizmarts\MailChimp\Test\Unit\Support;

abstract class QuoteItemStub extends \Magento\Quote\Model\Quote\Item
{
    abstract public function getProductId();
}
