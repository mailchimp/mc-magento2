<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Block\Pixel;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Exposes current product data as JSON for the Mailchimp Pixel JS module.
 * Rendered on catalog_product_view pages (PDP).
 */
class Product extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param Template\Context            $context
     * @param MailChimpHelper             $helper
     * @param Registry                    $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param array                       $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper             = $helper;
        $this->registry           = $registry;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Returns the current product or null.
     *
     * @return CatalogProduct|null
     */
    protected function getCurrentProduct(): ?CatalogProduct
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Returns the product data array for PRODUCT_VIEWED payload.
     *
     * @return array
     */
    public function getProductData(): array
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return [];
        }

        $store    = $this->_storeManager->getStore();
        $currency = $store->getCurrentCurrencyCode();

        $categories = [];
        foreach ($product->getCategoryIds() as $catId) {
            try {
                $cat          = $this->categoryRepository->get($catId, $store->getId());
                $categories[] = $cat->getName();
            } catch (NoSuchEntityException $e) {
                // skip missing category
            }
        }

        $imageUrl = '';
        try {
            $imageUrl = $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                . 'catalog/product' . $product->getImage();
        } catch (\Exception $e) {
            // skip
        }

        return [
            'id'         => (string)$product->getId(),
            'productId'  => (string)$product->getId(),
            'title'      => $product->getName(),
            'price'      => (float)$product->getFinalPrice(),
            'currency'   => $currency,
            'sku'        => (string)$product->getSku(),
            'imageUrl'   => $imageUrl,
            'productUrl' => $product->getProductUrl(),
            'vendor'     => '',
            'categories' => $categories,
        ];
    }

    /**
     * Only render when module + pixel are enabled and a product is available.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId) || !$this->getCurrentProduct()) {
            return '';
        }
        return parent::_toHtml();
    }
}
