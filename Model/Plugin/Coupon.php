<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/23/17 1:32 PM
 * @file: Coupon.php
 */

namespace Ebizmarts\MailChimp\Model\Plugin;

class Coupon
{
    protected $_helper;

    /**
     * Coupon constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {

        $this->_helper  = $helper;
    }
    public function afterAfterDelete(\Magento\SalesRule\Model\Coupon $coupon)
    {
        $this->_helper->markEcommerceAsDeleted(
            $coupon->getCouponId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_CODE,
            $coupon->getRuleId()
        );
    }
}
