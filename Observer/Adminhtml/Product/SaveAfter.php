<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 1/18/18 12:30 PM
 * @file: SaveAfter.php
 */
namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->helper               = $helper;
        $this->date                 = $date;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Catalog\Model\Product $product
         */
        $product = $observer->getProduct();
        $mailchimpStore = $this->helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $product->getStoreId());
        $this->_updateProduct($mailchimpStore, $product->getId(), $this->date->gmtDate(), '', 1);


    }
    protected function _updateProduct($storeId, $entityId, $sync_delta, $sync_error, $sync_modified)
    {
        $this->helper->saveEcommerceData(
            $storeId,
            $entityId,
            $sync_delta,
            $sync_error,
            $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
        );
    }
}
