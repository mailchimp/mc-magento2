<?php

namespace Ebizmarts\MailChimp\Test\Unit\Support;

abstract class QuoteAddressStub extends \Magento\Quote\Model\Quote\Address
{
    abstract public function getTaxAmount();

    abstract public function getShippingAmount();

    abstract public function getDiscountAmount();
}
