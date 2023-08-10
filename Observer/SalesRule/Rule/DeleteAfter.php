<?php

namespace Ebizmarts\MailChimp\Observer\SalesRule\Rule;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class DeleteAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    protected $_ecommerce;
    /**
     * @var SyncHelper
     */
    protected $syncHelper;

    /**
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce,
        SyncHelper $syncHelper
    ) {
        $this->_ecommerce = $ecommerce;
        $this->syncHelper = $syncHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $this->syncHelper->markEcommerceAsDeleted($ruleId, \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE);
    }
}
