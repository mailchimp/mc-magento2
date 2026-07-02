<?php
/**
 * Ebizmarts_MailChimp
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Test\Unit\Block\Pixel;

use Ebizmarts\MailChimp\Block\Pixel\Product;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as CatalogCategory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private function makeBlock(
        ?CatalogProduct $product,
        string $currency = 'USD',
        string $baseUrl = 'https://example.com/',
        array $categoryMap = []
    ): Product {
        $registry = $this->createMock(Registry::class);
        $registry->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $store = $this->createMock(Store::class);
        $store->method('getCurrentCurrencyCode')->willReturn($currency);
        $store->method('getId')->willReturn(1);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn($baseUrl);

        $categoryRepo = $this->createMock(CategoryRepositoryInterface::class);
        $categoryRepo->method('get')
            ->willReturnCallback(function (int $id) use ($categoryMap) {
                if (!isset($categoryMap[$id])) {
                    throw new NoSuchEntityException();
                }
                $cat = $this->createMock(CatalogCategory::class);
                $cat->method('getName')->willReturn($categoryMap[$id]);

                return $cat;
            });

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->method('getStoreManager')->willReturn($storeManager);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $helper = $this->createMock(MailChimpHelper::class);

        return new Product($context, $helper, $registry, $categoryRepo);
    }

    private function makeProduct(
        string $id,
        string $name,
        float $price,
        string $sku,
        string $image,
        string $url,
        array $categoryIds = []
    ): CatalogProduct {
        $product = $this->getMockBuilder(CatalogProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn($name);
        $product->method('getFinalPrice')->willReturn($price);
        $product->method('getSku')->willReturn($sku);
        $product->method('getImage')->willReturn($image);
        $product->method('getProductUrl')->willReturn($url);
        $product->method('getCategoryIds')->willReturn($categoryIds);

        return $product;
    }

    public function testGetProductDataReturnsEmptyArrayWhenNoProduct(): void
    {
        $block = $this->makeBlock(null);

        $this->assertSame([], $block->getProductData());
    }

    public function testGetProductDataReturnsCorrectFields(): void
    {
        $product = $this->makeProduct(
            '42', 'Running Shoes', 89.99, 'SHOE-RUN-42',
            '/s/shoe.jpg', 'https://example.com/running-shoes'
        );
        $block = $this->makeBlock($product, 'USD', 'https://example.com/');

        $result = $block->getProductData();

        $this->assertSame('42', $result['id']);
        $this->assertSame('Running Shoes', $result['title']);
        $this->assertSame(89.99, $result['price']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame('SHOE-RUN-42', $result['sku']);
        $this->assertSame('https://example.com/running-shoes', $result['productUrl']);
        $this->assertIsArray($result['categories']);
    }

    public function testGetProductDataBuildsImageUrl(): void
    {
        $product = $this->makeProduct('1', 'Hat', 20.0, 'HAT-1', '/h/hat.jpg', '');
        $block   = $this->makeBlock($product, 'USD', 'https://example.com/');

        $result = $block->getProductData();

        $this->assertSame('https://example.com/catalog/product/h/hat.jpg', $result['imageUrl']);
    }

    public function testGetProductDataResolvesCategoryNames(): void
    {
        $product = $this->makeProduct('2', 'Jacket', 120.0, 'JACK-1', '/j/j.jpg', '', [3, 7]);
        $block   = $this->makeBlock($product, 'USD', 'https://example.com/', [3 => 'Outerwear', 7 => 'Men']);

        $result = $block->getProductData();

        $this->assertContains('Outerwear', $result['categories']);
        $this->assertContains('Men', $result['categories']);
    }

    public function testGetProductDataSkipsMissingCategories(): void
    {
        // Category 99 is not in the map → NoSuchEntityException → skipped
        $product = $this->makeProduct('3', 'Gloves', 25.0, 'GLV-1', '/g/g.jpg', '', [99]);
        $block   = $this->makeBlock($product, 'USD', 'https://example.com/', []);

        $result = $block->getProductData();

        $this->assertSame([], $result['categories']);
    }

    public function testGetProductDataTypeCasts(): void
    {
        $product = $this->makeProduct('10', 'Bag', 45.0, 'BAG-1', '/b/b.jpg', '');
        $block   = $this->makeBlock($product);

        $result = $block->getProductData();

        $this->assertIsString($result['id']);
        $this->assertIsFloat($result['price']);
        $this->assertIsString($result['sku']);
    }
}
