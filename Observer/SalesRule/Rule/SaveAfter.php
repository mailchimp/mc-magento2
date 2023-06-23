<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/19/17 4:14 PM
 * @file: Rule.php
 */

namespace Ebizmarts\MailChimp\Observer\SalesRule\Rule;

use Magento\Framework\Event\Observer;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    protected $_ecommerce;
    /**
     * @var SyncHelper
     */
    protected $syncHelper;
    protected $_date;

    /**
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce
     * @param SyncHelper $syncHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce,
        SyncHelper $syncHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {

        $this->_ecommerce   = $ecommerce;
        $this->syncHelper   = $syncHelper;
        $this->_date        = $date;
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
