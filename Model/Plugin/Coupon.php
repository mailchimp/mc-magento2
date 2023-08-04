<?php

namespace Ebizmarts\MailChimp\Model\Plugin;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class Coupon
{
    /**
     * @var SyncHelper
     */
    private $synHelper;

    /**
     * @param SyncHelper $synHelper
     */
    public function __construct(
        SyncHelper $synHelper
    ) {
        $this->synHelper = $synHelper;
    }

    public function afterAfterDelete(\Magento\SalesRule\Model\Coupon $coupon)
    {
        $this->synHelper->markEcommerceAsDeleted(
            $coupon->getCouponId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_CODE,
            $coupon->getRuleId()
        );
    }
}
