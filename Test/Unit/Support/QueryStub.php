<?php

namespace Ebizmarts\MailChimp\Test\Unit\Support;

abstract class QueryStub extends \Magento\Search\Model\Query
{
    abstract public function getNumResults();
}
