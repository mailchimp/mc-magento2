<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 2/15/17 3:38 PM
 * @file: SaveAfter.php
 */

namespace Ebizmarts\MailChimp\Observer\Sales\Order;

use Magento\Framework\Event\Observer;

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

    /**
     * SaveAfter constructor.
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        $this->_ecommerce   = $ecommerce;
        $this->_helper      = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $mailchimpStoreId = $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $order->getStoreId());
        $ecom = $this->_ecommerce->getByStoreIdType($mailchimpStoreId, $order->getId(), 'ORD');
        if ($ecom) {
            $ecom->setMailchimpSyncModified(1);
            $ecom->getResource()->save($ecom);
        }

    }
}
