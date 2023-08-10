<?php

namespace Ebizmarts\MailChimp\Observer\SalesRule\Rule;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    protected $_ecommerce;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    protected $_date;
    private $syncHelper;

    /**
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        SyncHelper $syncHelper
    ) {
        $this->_ecommerce = $ecommerce;
        $this->_helper = $helper;
        $this->_date = $date;
        $this->syncHelper = $syncHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $rule \Magento\SalesRule\Model\Rule
         */
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $this->syncHelper->markRegisterAsModified($ruleId, \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE);
    }
}
