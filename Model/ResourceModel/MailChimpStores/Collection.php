<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpStores;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpStores::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpStores::class
        );
    }
}
