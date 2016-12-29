<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/10/16 5:22 PM
 * @file: Product.php
 */

namespace Ebizmarts\MailChimp\Model\Api;

class Product
{
    const DOWNLOADABLE  = 'downloadable';
    const PRODUCTIMAGE  = 'product_small_image';
    const MAX           = 100;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $_productCollection;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;
    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $_stockItemRepository;
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $_categoryRepository;
    /**
     * @var string
     */
    protected $_batchId;

    /**
     * Product constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
    )
    {
        $this->_productRepository   = $productRepository;
        $this->_helper              = $helper;
        $this->_date                = $date;
        $this->_productCollection   = $productCollection;
        $this->_imageHelper         = $imageHelper;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_categoryRepository  = $categoryRepository;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT. '_' . $this->_date->gmtTimestamp();
    }
    public function _sendProducts($storeId)
    {
        $batchArray = array();
        $counter = 0;
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE);
        $collection = $this->_productCollection;
        $collection->setStoreId($storeId);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter(
                array(
                    array('attribute'=>'mailchimp_sync_delta','null'=>true),
                    array('attribute'=>'mailchimp_sync_delta','lt'=>$this->_helper->getMCMinSyncDateFlag()),
                    array('attribute'=>'mailchimp_sync_modified', 'eq'=>1)
                ), '', 'left'
            );
        $collection->getSelect()->limit(self::MAX);
        foreach($collection as $item)
        {
            /**
             * @var $product \Magento\Catalog\Model\Product
             */
            $product = $this->_productRepository->get($item->getSku());
            if ($product->getMailchimpSyncModified() && $product->getMailchimpSyncDelta() &&
                $product->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag()) {
                $product->setData("mailchimp_sync_error", "");
                $product->setData('mailchimp_sync_modified', 0);
                $product->setData("mailchimp_sync_modified", $this->_date->gmtDate());
                $product->setHasDataChanges(true);
//                $product->getResource()->save($product);
                $product->getResource()->saveAttribute($product,'mailchimp_sync_error');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
                continue;
            } else {
                $data = $this->_buildNewProductRequest($product, $mailchimpStoreId);
            }
            if (!empty($data)) {
                $batchArray[$counter] = $data;
                $counter++;

                //update product delta
                $product->setData("mailchimp_sync_delta", $this->_date->gmtDate());
                $product->setData("mailchimp_sync_error", "");
                $product->setData('mailchimp_sync_modified', 0);
                $product->setHasDataChanges(true);
                $product->getResource()->save($product);
                $product->getResource()->saveAttribute($product,'mailchimp_sync_error');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
            } else {
                $product->setData("mailchimp_sync_delta", $this->_date->gmtDate());
                $product->setData("mailchimp_sync_error", "This product type is not supported on MailChimp.");
                $product->setData('mailchimp_sync_modified', 0);
                $product->setHasDataChanges(true);
                $product->getResource()->save($product);
                $product->getResource()->saveAttribute($product,'mailchimp_sync_error');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
                $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
            }
        }
        return $batchArray;

    }
    protected function _buildNewProductRequest(\Magento\Catalog\Model\Product $product, $mailchimpStoreId)
    {
        $variantProducts = array();
        switch($product->getTypeId()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                $variantProducts[] = $product;
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $childProducts = $product->getTypeInstance()->getChildrenIds($product->getId());
                $variantProducts[] = $product;
                if (count($childProducts[0])) {
                    foreach ($childProducts[0] as $childId) {
                        $variantProducts[] = $this->_productRepository->getById($childId);
                    }
                }
                break;
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
                $variantProducts[] = $product;
                break;
            case self::DOWNLOADABLE:
                $variantProducts[] = $product;
                break;
            default:
                return array();
        }
        $bodyData = $this->_buildProductData($product, false, $variantProducts);
        try {
            $body = json_encode($bodyData,JSON_HEX_APOS|JSON_HEX_QUOT);

//            $this->_helper->log($body);

        } catch (Exception $e) {
            //json encode failed
            $this->_helper->log("Product " . $product->getId() . " json encode failed");
            return array();
        }
        $data = array();
        $data['method'] = "POST";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products";
        $data['operation_id'] = $this->_batchId . '_' . $product->getId();
        $data['body'] = $body;
        return $data;
    }
    protected function _buildOldProductRequest($product, $mailchimpStoreId)
    {
        return "";
    }
    protected function _buildProductData(\Magento\Catalog\Model\Product $product,  $isVarient = true, $variants = null)
    {
        $data = array();

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();
        $data["url"] = $product->getProductUrl();
        $data["image_url"] =$this->_imageHelper->init($product,self::PRODUCTIMAGE)->setImageFile($product->getImage())->getUrl();
        $data["published_at_foreign"] = "";
        if ($isVarient) {
            //this is for a varient product
            $data["sku"] = $product->getSku();
            $data["price"] = $product->getPrice();

            //stock
            $stock =$this->_stockItemRepository->get($product->getId());
            $data["inventory_quantity"] = (int)$stock->getQty();
            $data["backorders"] = (string)$stock->getBackorders();

            $data["visibility"] = $product->getVisibility();

        } else {
            //this is for a root product
            if($product->getData('description')) {
                $data["description"] = $product->getData('description');
            }

            //mailchimp product type (magento category)
            $categoryIds = $product->getCategoryIds();
            if (count($categoryIds)) {
                $category = $this->_categoryRepository->get($categoryIds[0]);
                $data["type"] = $category->getName();
            }

            //missing data
            $data["vendor"] = "";
            $data["handle"] = "";

            //variants
            $data["variants"] = array();
            foreach ($variants as $variant) {
                $data["variants"][] = $this->_buildProductData($variant);
            }
        }

        return $data;
    }

    public function sendModifiedProduct(\Magento\Sales\Model\Order $order,$mailchimpStoreId)
    {
        $data = array();
        $batchId = \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT . '_' . $this->_date->gmtTimestamp();
        $items = $order->getAllVisibleItems();
        foreach ($items as $item)
        {
            $product = $this->_productRepository->getById($item->getProductId());
            if ($product->getId()!=$item->getProductId()||$product->getTypeId()=='bundle'||$product->getTypeId()=='grouped') {
                continue;
            }

            if ($product->getMailchimpSyncModified() && $product->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag()) {
                $data[] = $this->_buildOldProductRequest($product, $mailchimpStoreId);
                $this->_updateProduct($product);
            } elseif (!$product->getMailchimpSyncDelta() || $product->getMailchimpSyncDelta() < $this->_helper->getMCMinSyncDateFlag()) {
                $data[] = $this->_buildNewProductRequest($product, $mailchimpStoreId);
                $this->_updateProduct($product);
            }
        }
        return $data;
    }
    protected function _updateProduct($product)
    {
        $product->setData("mailchimp_sync_delta", $this->_date->gmtDate());
        $product->setData("mailchimp_sync_error", "");
        $product->setData('mailchimp_sync_modified', 0);
        $product->setHasDataChanges(true);
        $product->getResource()->save($product);
        $product->getResource()->saveAttribute($product,'mailchimp_sync_error');
        $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
        $product->getResource()->saveAttribute($product,'mailchimp_sync_modified');
    }
}