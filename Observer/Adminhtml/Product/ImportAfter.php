<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class ImportAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    private $syncHelper;

    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        CollectionFactory $productCollectionFactory,
        SyncHelper $syncHelper
    )
    {
        $this->helper = $helper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->syncHelper = $syncHelper;
    }
    public function execute(Observer $observer)
    {
        try {
            $bunch = $observer->getBunch();
            $counter = 0;
            $skus = [];
            foreach ($bunch as $product) {
                if ($counter % 100 == 0 && count($skus)) {
                    $this->updateSkus($skus);
                    $skus =[];
                }
                $sku = $product['sku'];
                if (key_exists('_store', $product)&&!empty($product['_store'])) {
                    $storeId = $product['_store'];
                } else {
                    $storeId = 0;
                }
                $skus[$storeId][]=$sku;
                $counter++;
            }
            if (count($skus)) {
                $this->updateSkus($skus);
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }
    protected function updateSkus($skus)
    {
        foreach ($skus as $storeId => $storeskus) {
            /**
             * @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
             */
            $collection = $this->productCollectionFactory->create();
            $collection->addStoreFilter($storeId);
            $collection->addFieldToFilter('sku', ['in'=>$storeskus]);
            $collection->addFieldToSelect('id');
            $productIds = [];
            foreach ($collection as $item) {
                $productIds[] = $item->getId();
            }
            $this->markAsModified($storeId, $productIds);
        }

    }
    protected function markAsModified($storeId,$productsIds)
    {
        $mailchimpStoreId = $this->helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $storeId
        );
        $this->syncHelper->markAllAsModifiedByIds($mailchimpStoreId, $productsIds, \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT);

    }
}
