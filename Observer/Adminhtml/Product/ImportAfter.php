<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

use Magento\Framework\Event\Observer;

class ImportAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    protected $productRepository;

    /**
     * ImportAfter constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Catalog\Model\ProductRepository $productRepository
    )
    {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
    }
    public function execute(Observer $observer)
    {
        try {
            $bunch = $observer->getBunch();
            foreach ($bunch as $product) {
                $sku = $product['sku'];
                $storeId = $product['_store'];
                $pro = $this->productRepository->get($sku, false,$storeId);
                $id = $pro->getId();
                $storeId = $pro->getStoreId();
                $this->_updateProduct($pro->getStoreId(), $pro->getId(), null,null,1);
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
    protected function _updateProduct(
        $storeId,
        $entityId,
        $sync_delta = null,
        $sync_error = null,
        $sync_modified = null
    ) {
        $mailchimpStoreId = $this->helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $storeId
        );
        $this->helper->saveEcommerceData(
            $mailchimpStoreId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}