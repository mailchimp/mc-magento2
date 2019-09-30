<?php
/**
 * Ebizmarts_mc-magento22 Magento component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_mc-magento2
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
namespace Ebizmarts\MailChimp\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface as SalesInvoiceRepositoryInterface;

class Invoice
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;

    /**
     * Ship constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        $this->_helper  = $helper;
    }
    public function afterSave(
        SalesInvoiceRepositoryInterface $subject,
        InvoiceInterface $invoice
    ) {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $invoice->getStoreId()
        );
        $this->_helper->saveEcommerceData(
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
