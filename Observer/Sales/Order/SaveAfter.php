<?php

namespace Ebizmarts\MailChimp\Observer\Sales\Order;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $order->getStoreId()
        );
        if ($order->getMailchimpSent() != \Ebizmarts\MailChimp\Helper\Data::NEVERSYNC) {
            $this->syncHelper->saveEcommerceData(
                $mailchimpStoreId,
                $order->getId(),
                \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
                null,
                null,
                1,
                null,
                null,
                \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC
            );
        }
    }
}
