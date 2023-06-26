<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/19/17 5:26 PM
 * @file: DeleteAfter.php
 */
namespace Ebizmarts\MailChimp\Observer\SalesRule\Rule;

use Magento\Framework\Event\Observer;
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

        $this->_ecommerce   = $ecommerce;
        $this->syncHelper   = $syncHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $this->syncHelper->markEcommerceAsDeleted($ruleId, \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE);
    }
}
