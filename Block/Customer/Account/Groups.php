<?php

namespace Ebizmarts\MailChimp\Block\Customer\Account;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
class Groups extends Template
{
    protected $mailChimpHelper;
    protected $context;


    public function __construct(
        Context $context,
        MailChimpHelper $helper,
        array $data
    )
    {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->mailChimpHelper = $helper;
    }

    public function getGroups()
    {
        $store = $this->context->getStoreManager()->getStore();
        $groups = $this->mailChimpHelper->getInterest($store->getId());
        $this->mailChimpHelper->log($groups);
        return $groups;
    }

}
