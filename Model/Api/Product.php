<?php
/**

mc-magento2 Magento Component
@category Ebizmarts
@Package mc-magento2
@author Ebizmarts Team info@ebizmarts.com
@copyright Ebizmarts (http://ebizmarts.com)
@license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
@Date: 10/10/16 5:22 PM
@file: Product.php
 */
namespace Ebizmarts\MailChimp\Model\Api;

class Product
{
    const DOWNLOADABLE = 'downloadable';
    const PRODUCTIMAGE = 'product_small_image';
    const MAX = 100;

    protected $_parentImage = null;
    protected $_childtUrl   = null;
    protected $productPrice = null;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollection;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $_categoryRepository;
    /**
     * @var string
     */
    protected $_batchId;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory
     */
    protected $_chimpSyncEcommerce;
    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $_configurable;
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $taxHelper;
    /**
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $_option;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollection;
    protected $includingTaxes;

    /**
     * Product constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection
     * @param \Magento\Catalog\Helper\Data $taxHelper
     * @param \Magento\Catalog\Model\Product\Option $option
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        \Magento\Catalog\Helper\Data $taxHelper,
        \Magento\Catalog\Model\Product\Option $option
    ) {

        $this->_productRepository   = $productRepository;
        $this->_helper              = $helper;
        $this->_productCollection   = $productCollection;
        $this->_imageHelper         = $imageHelper;
        $this->_stockRegistry       = $stockRegistry;
        $this->_categoryRepository  = $categoryRepository;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_configurable        = $configurable;
        $this->_option              = $option;
        $this->_categoryCollection  = $categoryCollection;
        $this->taxHelper            = $taxHelper;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT. '_' .
            $this->_helper->getGmtTimeStamp();
    }
    public function _sendProducts($magentoStoreId)
    {
        $batchArray = [];
        $counter = 0;
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $magentoStoreId
        );
        $this->includingTaxes = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_INCLUDING_TAXES,
            $magentoStoreId
        );
        $this->_markSpecialPrices($magentoStoreId, $mailchimpStoreId);
        $collection = $this->_getCollection();
        $collection->addFieldToFilter("type_id", ["nin"=>[\Magento\Catalog\Model\Product\Type::TYPE_BUNDLE, "grouped"]]);
        $collection->addStoreFilter($magentoStoreId);
        $collection->getSelect()->joinLeft(
            ['m4m' => $this->_helper->getTableName('mailchimp_sync_ecommerce')],
            "m4m.related_id = e.entity_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null OR (m4m.mailchimp_sync_delta > '".
            $this->_helper->getMCMinSyncDateFlag().
            "' and m4m.mailchimp_sync_modified = 1)");
        $collection->getSelect()->limit(self::MAX);
        foreach ($collection as $item) {
            /**
             * @var $product \Magento\Catalog\Model\Product
             */
            $product = $this->_productRepository->getById($item->getId(), false, $magentoStoreId);
            if ($item->getMailchimpSyncModified() && $item->getMailchimpSyncDelta() &&
                $item->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag()) {
                $batchArray = array_merge($this->_buildOldProductRequest(
                    $product,
                    $this->_batchId,
                    $mailchimpStoreId,
                    $magentoStoreId
                ), $batchArray);
                $this->_updateProduct($mailchimpStoreId, $product->getId());
                continue;
            } else {
                $data = $this->_buildNewProductRequest($product, $mailchimpStoreId, $magentoStoreId);
            }
            if (!empty($data)) {
                $batchArray[$counter] = $data;
                $counter++;

                //update product delta
                $this->_updateProduct($mailchimpStoreId, $product->getId());
            } else {
                $this->_updateProduct(
                    $mailchimpStoreId,
                    $product->getId(),
                    $this->_helper->getGmtDate(),
                    "This product type is not supported on MailChimp.",
                    0
                );
            }
        }
        return $batchArray;
    }
    protected function _markSpecialPrices($magentoStoreId, $mailchimpStoreId)
    {
        /**
         * get the products with current special price that are not synced and mark it as modified
         */
        $collection = $this->_getCollection();
        $collection->addStoreFilter($magentoStoreId);
        $collection->addAttributeToFilter(
            'special_price',
            ['gt'=>0],
            'left'
        )->addAttributeToFilter(
            'special_from_date',
            ['or' => [ 0 => ['date' => true,
                'to' => date('Y-m-d', time()).' 23:59:59'],
                1 => ['is' => new \Zend_Db_Expr(
                    'null'
                )],]],
            'left'
        )->addAttributeToFilter(
            'special_to_date',
            ['or' => [ 0 => ['date' => true,
                'from' => date('Y-m-d', time()).' 00:00:00'],
                1 => ['is' => new \Zend_Db_Expr(
                    'null'
                )],]],
            'left'
        );
        $collection->getSelect()->joinLeft(
            ['mc' => $collection->getTable('mailchimp_sync_ecommerce')],
            "mc.type = 'PRO' AND mc.related_id = e.entity_id AND mc.mailchimp_sync_modified = 0 ".
            $collection->getConnection()->quoteInto(" AND  mc.mailchimp_store_id = ?", $mailchimpStoreId) .
            " and mc.mailchimp_sync_delta <  at_special_from_date.value"
        );
        $collection->getSelect()->where('mc.mailchimp_sync_delta is not null');
        foreach ($collection as $item) {
            $this->_updateProduct($mailchimpStoreId, $item->getEntityId(), null, null, 1);
        }
        /**
         * get the products that was synced when it have special price and have no more special price
         */
        $collection2 = $this->_getCollection();
        $collection2->addStoreFilter($magentoStoreId);
        $collection2->addAttributeToFilter(
            'special_price',
            ['gt'=>0],
            'left'
        )->addAttributeToFilter(
            'special_to_date',
            ['or' => [ 0 => ['date' => true,
                'to' => date('Y-m-d', time()).' 00:00:00'],
            ]],
            'left'
        );
        $collection2->getSelect()->joinLeft(
            ['mc' => $collection2->getTable('mailchimp_sync_ecommerce')],
            "mc.type = 'PRO' and mc.related_id = e.entity_id and mc.mailchimp_sync_modified = 0 ".
            $collection->getConnection()->quoteInto(" AND  mc.mailchimp_store_id = ?", $mailchimpStoreId) .
            " and mc.mailchimp_sync_delta < at_special_to_date.value",
            []
        );
        $collection2->getSelect()->where('mc.mailchimp_sync_delta is not null');
        foreach ($collection2 as $item) {
            $this->_updateProduct($mailchimpStoreId, $item->getEntityId(), null, null, 1);
        }
    }
    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getCollection()
    {
        return $this->_productCollection->create();
    }
    protected function _buildNewProductRequest(
        \Magento\Catalog\Model\Product $product,
        $mailchimpStoreId,
        $magentoStoreId
    ) {

        $variantProducts = [];
        switch ($product->getTypeId()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                $variantProducts[] = $product;
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $childProducts = $product->getTypeInstance()->getChildrenIds($product->getId());
                $variantProducts[] = $product;
                if (count($childProducts[0])) {
                    foreach ($childProducts[0] as $childId) {
                        $variantProducts[] = $this->_productRepository->getById($childId, false, $magentoStoreId);
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
                return [];
        }
        $bodyData = $this->_buildProductData($product, $magentoStoreId, false, $variantProducts);
        $body = json_encode($bodyData, JSON_HEX_APOS|JSON_HEX_QUOT);
        if ($body === false) {
            $jsonError = json_last_error();
            $jsonErrorMsg = json_last_error_msg();
            $this->_helper->log("");
            $this->_helper->log("$jsonErrorMsg for product [".$product->getId()."]");
            return [];

        } else {
            $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::PRO_NEW);
            $data = [];
            $data['method'] = "POST";
            $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products";
            $data['operation_id'] = $this->_batchId . '_' . $product->getId();
            $data['body'] = $body;
            $this->productPrice = null;
        }
        return $data;
    }
    protected function _buildOldProductRequest(
        \Magento\Catalog\Model\Product $product,
        $batchId,
        $mailchimpStoreId,
        $magentoStoreId
    ) {
        $operations = [];
        $variantProducts = [];
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE ||
            $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
            $product->getTypeId() == "downloadable") {
            $data = $this-> _buildProductData($product, $magentoStoreId);
            $variantProducts [] = $product;
            // $parentIds = $product->getTypeInstance()->getParentIdsByChild($product->getId());
            $parentIds = $this->_configurable->getParentIdsByChild($product->getId());

            //add or update variant
            foreach ($parentIds as $parentId) {
                $productSync = $this->_chimpSyncEcommerce->create()->getByStoreIdType(
                    $mailchimpStoreId,
                    $parentId,
                    \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
                );
                if ($productSync->getMailchimpSyncDelta()) {
                    $variendata = [];
                    $variendata["id"] = $data["id"];
                    $variendata["title"] = $data["title"];
                    $variendata["url"] = $data["url"];
                    $variendata["sku"] = $data["sku"];
                    $variendata["price"] = $data["price"];
                    $variendata["inventory_quantity"] = $data["inventory_quantity"];
                    $variendata["image_url"] = $data["image_url"];
                    $variendata["backorders"] = $data["backorders"];
                    $variendata["visibility"] = $data["visibility"];
                    $productdata = [];
                    $productdata['method'] = "PUT";
                    $productdata['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/" .
                        $parentId . '/variants/' . $data['id'];
                    $productdata['operation_id'] = $batchId . '_' . $parentId;
                    $body = json_encode($variendata);
                    if ($body===false) {
                        $jsonErrorMsg = json_last_error_msg();
                        $this->_helper->log("");
                        $this->_helper->log("$jsonErrorMsg for product [".$product->getId()."]");
                        continue;
                    }
                    $productdata['body'] = $body;
                    $operations[] = $productdata;
                }
            }
        } elseif ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $childProducts = $product->getTypeInstance()->getChildrenIds($product->getId());
            $variantProducts[] = $product;
            if (count($childProducts[0])) {
                foreach ($childProducts[0] as $childId) {
                    $variantProducts[] = $this->_productRepository->getById($childId, false, $magentoStoreId);
                }
            }
        } else {
            return [];
        }

        $bodyData = $this->_buildProductData($product, $magentoStoreId, false, $variantProducts);
        $body = json_encode($bodyData, JSON_HEX_APOS|JSON_HEX_QUOT);
        if ($body===false) {
            $jsonErrorMsg = json_last_error_msg();
            $this->_helper->log("");
            $this->_helper->log("$jsonErrorMsg for product [".$product->getId()."]");
            return [];
        }
        $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::PRO_MOD);
        $data = [];
        $data['method'] = "PATCH";
        $data['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/products/".$product->getId();
        $data['operation_id'] = $this->_batchId . '_' . $product->getId();
        $data['body'] = $body;
        $operations[] = $data;
        $this->productPrice = null;
        return $operations;
    }
    protected function _buildProductData(
        \Magento\Catalog\Model\Product $product,
        $magentoStoreId,
        $isVarient = true,
        $variants = null
    ) {

        $data = [];
        $parent = null;

        //data applied for both root and varient products
        $data["id"] = $product->getId();
        $data["title"] = $product->getName();
        $data["url"] = $product->getProductUrl();
        $data["image_url"] = '';
        if ($product->getImage() && $product->getImage()!='no_selection') {
            $filePath = 'catalog/product'.$product->getImage();
            $data["image_url"] = $this->_helper->getBaserUrl(
                $magentoStoreId,
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ).$filePath;
        } elseif ($this->_parentImage) {
            $data['image_url'] = $this->_parentImage;
        } else {
            $parent = $this->_getParent($product->getId(), $magentoStoreId);
            if ($parent && $parent->getImage() && $parent->getImage()!='no_selection') {
                $filePath = 'catalog/product'.$parent->getImage();
                $data["image_url"] = $this->_helper->getBaserUrl(
                    $magentoStoreId,
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ).$filePath;
            }
        }
        $data["published_at_foreign"] = "";
        if ($isVarient) {
            //this is for a varient product
            $data["sku"] = $product->getSku() ? $product->getSku() : '';
            if ($this->productPrice) {
                $data['price'] = $this->productPrice;
            } else {
                $data['price'] = $this->_getProductPrice($product);
            }

            //stock
            $stock = $this->_stockRegistry->getStockItem($product->getId(), $magentoStoreId);
            $data["inventory_quantity"] = (int)$stock->getQty();
            $data["backorders"] = (string)$stock->getBackorders();
            if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                $tailUrl = '';
                $data["visibility"] = 'false';
                if (!$parent) {
                    $parent = $this->_getParent($product->getId(), $magentoStoreId);
                }
                if ($parent) {
                    $options = $parent->getTypeInstance()->getConfigurableAttributesAsArray($parent);
                    foreach ($options as $option) {
                        if (strlen($tailUrl)) {
                            $tailUrl .= '&';
                        } else {
                            $tailUrl .= '?';
                        }
                        $tailUrl .= $option['attribute_code'] . "=" . $product->getData($option['attribute_code']);
                    }
                    $this->_childtUrl = $data['url'] = $parent->getProductUrl() . $tailUrl;
                    if (empty($data['image_url'])) {
                        $filePath = 'catalog/product'.$parent->getImage();
                        $data["image_url"] = $this->_helper->getBaserUrl(
                            $magentoStoreId,
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ).$filePath;
                    }
                }
            } else {
                $data["visibility"] = 'true';
            }
        } else {
            //this is for a root product
            if ($product->getData('description')) {
                $data["description"] = $product->getData('description');
            }

            $categoryName = $this->getProductCategories($product, $magentoStoreId);
            if ($categoryName) {
                $data['type'] = $data['vendor'] = $categoryName;
            }

            //missing data
            $data["handle"] = "";
            if (!empty($data['image_url'])) {
                $this->_parentImage = $data['image_url'];
            }
            //variants
            $data["variants"] = [];
            // put the min price of the simples as the price of the parent
            foreach ($variants as $variant) {
                if ($variant && $variant->getId() != $product->getId()) {
                    $variantPrice = $this->_getProductPrice($variant);
                    if ($this->productPrice) {
                        if ($variantPrice < $this->productPrice) {
                            $this->productPrice = $variantPrice;
                        }
                    } else {
                        $this->productPrice = $variantPrice;
                    }
                }
            }
            /**
             * @var $variant \Magento\Catalog\Model\Product
             */
            foreach ($variants as $variant) {
                if ($variant) {
                    if ($variant->getId() != $product->getId()) {
                        $this->productPrice = null;
                    }
                    $data["variants"][] = $this->_buildProductData($variant, $magentoStoreId);
                }
            }
            if ($this->_childtUrl) {
                if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE ||
                    $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
                    $product->getTypeId() == "downloadable") {
                    $data["url"] = $this->_childtUrl;
                }
                $this->_childtUrl = null;
            }
            $this->_parentImage = null;
        }

        return $data;
    }

    protected function _getParent($productId, $magentoStoreId)
    {
        $parentIds =$this->_configurable->getParentIdsByChild($productId);
        $parent = null;
        foreach ($parentIds as $id) {
            $parent = $this->_productRepository->getById($id, false, $magentoStoreId);
            if ($parent->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                break;
            } else {
                $parent = null;
            }
        }
        return $parent;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $storeId
     */
    protected function getProductCategories(\Magento\Catalog\Model\Product $product, $storeId)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryNames = [];
        $categoryName = null;
        if (is_array($categoryIds) && count($categoryIds)) {
            $collection = $this->_categoryCollection->create();
            $collection->addAttributeToSelect(['name'])
                ->setStoreId($storeId)
                ->addAttributeToFilter('is_active', ['eq'=>'1'])
                ->addAttributeToFilter('entity_id', ['in'=>$categoryIds])
                ->addAttributeToSort('level', 'asc');
            foreach ($collection as $category) {
                $categoryNames[] = $category->getName();
            }
            $categoryName = (count($categoryNames)) ? implode(" - ", $categoryNames) : 'None';
        }
        return $categoryName;
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $mailchimpStoreId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendModifiedProduct(\Magento\Sales\Model\Order $order, $mailchimpStoreId, $magentoStoreId)
    {
        $data = [];
        $batchId = \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT . '_' . $this->_helper->getGmtTimeStamp();
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            //@todo get from the store not the default
            $product = $this->_productRepository->getById($item->getProductId(), false, $magentoStoreId);
            $productSyncData = $this->_chimpSyncEcommerce->create()->getByStoreIdType(
                $mailchimpStoreId,
                $product->getId(),
                \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
            );
            if ($product->getId()!=$item->getProductId() || (
                    $product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE &&
                    $product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL &&
                    $product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE &&
                    $product->getTypeId() != "downloadable")) {
                $this->_helper->log('The product with id ['.$product->getId().
                    '] is not supported ['.$product->getTypeId().']');
                continue;
            }
            if ($productSyncData->getMailchimpSyncModified() &&
                $productSyncData->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag()) {
                $data = array_merge(
                    $data,
                    $this->_buildOldProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId)
                );
                $this->_updateProduct($mailchimpStoreId, $product->getId());
            } elseif (!$productSyncData->getMailchimpSyncDelta() ||
                $productSyncData->getMailchimpSyncDelta() < $this->_helper->getMCMinSyncDateFlag()) {
                $data[] = $this->_buildNewProductRequest($product, $mailchimpStoreId, $magentoStoreId);
                $this->_updateProduct($mailchimpStoreId, $product->getId());
            }
        }
        return $data;
    }

    public function sendQuoteModifiedProduct(\Magento\Quote\Model\Quote $quote, $mailchimpStoreId, $magentoStoreId)
    {
        $data = [];
        $batchId = \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT . '_' . $this->_helper->getGmtTimeStamp();
        $items = $quote->getAllVisibleItems();
        /**
         * @var $item \Magento\Quote\Model\Quote\Item
         */
        foreach ($items as $item) {
            //@todo get from the store not the default
            $product = $this->_productRepository->getById($item->getProductId(), false, $magentoStoreId);
            $productSyncData = $this->_chimpSyncEcommerce->create()->getByStoreIdType(
                $mailchimpStoreId,
                $product->getId(),
                \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
            );
            if ($product->getId()!=$item->getProductId() || (
                    $product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE &&
                    $product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL &&
                    $product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE &&
                    $product->getTypeId() != "downloadable")) {
                $this->_helper->log(
                    'The product with id ['.$product->getId().'] is not supported ['.$product->getTypeId().']'
                );
                continue;
            }

            if ($productSyncData->getMailchimpSyncModified() &&
                $productSyncData->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag()) {
                $data = array_merge(
                    $data,
                    $this->_buildOldProductRequest($product, $batchId, $mailchimpStoreId, $magentoStoreId)
                );
                $this->_updateProduct($mailchimpStoreId, $product->getId());
            } elseif (!$productSyncData->getMailchimpSyncDelta() ||
                $productSyncData->getMailchimpSyncDelta() < $this->_helper->getMCMinSyncDateFlag()) {
                $data[] = $this->_buildNewProductRequest($product, $mailchimpStoreId, $magentoStoreId);
                $this->_updateProduct($mailchimpStoreId, $product->getId());
            }
        }
        return $data;
    }
    protected function _getProductPrice(\Magento\Catalog\Model\Product $product)
    {
        if ($this->includingTaxes) {
            $price = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true);
        } else {
            $price = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), false);
        }
        return $price;
    }
    /**
     * @param $storeId
     * @param $entityId
     * @param $sync_delta
     * @param $sync_error
     * @param $sync_modified
     */
    protected function _updateProduct(
        $storeId,
        $entityId,
        $sync_delta = null,
        $sync_error = null,
        $sync_modified = null
    ) {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}
