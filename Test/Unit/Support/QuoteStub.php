<?php

namespace Ebizmarts\MailChimp\Test\Unit\Support;

abstract class QuoteStub extends \Magento\Quote\Model\Quote
{
    abstract public function getSubtotal();

    abstract public function getGrandTotal();

    abstract public function getCouponCode();
}
