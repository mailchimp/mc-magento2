<?php

namespace Ebizmarts\MailChimp\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice as SalesInvoice;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class Invoice
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
        $this->_helper  = $helper;
        $this->syncHelper = $syncHelper;
    }
    public function afterSave(
        SalesInvoice $subject,
        InvoiceInterface $invoice
    ) {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $invoice->getStoreId()
        );
        $this->syncHelper->saveEcommerceData(
            $mailchimpStoreId,
            $invoice->getOrderId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
            null,
            null,
            1,
            null,
            null,
            \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC
        );
        return $invoice;
    }
}
