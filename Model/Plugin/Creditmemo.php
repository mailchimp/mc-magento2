<?php

namespace Ebizmarts\MailChimp\Model\Plugin;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Magento\Sales\Api\CreditmemoRepositoryInterface as SalesCreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class Creditmemo
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper
    ) {
        $this->_helper = $helper;
        $this->syncHelper = $syncHelper;
    }

    public function afterSave(
        SalesCreditmemoRepositoryInterface $subject,
        CreditmemoInterface $creditmemo
    ) {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $creditmemo->getStoreId()
        );
        $this->syncHelper->saveEcommerceData(
            $mailchimpStoreId,
            $creditmemo->getOrderId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
            null,
            null,
            1,
            null,
            null,
            \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC
        );

        return $creditmemo;
    }
}
