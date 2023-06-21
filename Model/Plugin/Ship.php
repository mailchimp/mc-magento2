<?php
/**
 * Ebizmarts_mc-magento22 Magento component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_mc-magento22
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
namespace Ebizmarts\MailChimp\Model\Plugin;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment as SalesShipment;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class Ship
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
        SalesShipment $subject,
        ShipmentInterface $shipment
    ) {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $shipment->getStoreId()
        );
        $this->syncHelper->saveEcommerceData(
            $mailchimpStoreId,
            $shipment->getOrderId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
            null,
            null,
            1,
            null,
            null,
            \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC
        );
        return $shipment;
    }
}
