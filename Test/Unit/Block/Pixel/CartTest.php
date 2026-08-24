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

use Ebizmarts\MailChimp\Block\Pixel\Cart;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Product\LeafProductIdResolver;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    /**
     * Real QuoteItem mocks: the block now hands the item to LeafProductIdResolver,
     * which type-hints QuoteItem, so a plain DataObject no longer fits.
     *
     * @param  string   $productId
     * @param  string   $name
     * @param  float    $price
     * @param  string   $sku
     * @param  int      $qty
     * @param  string   $productType
     * @param  int|null $childProductId
     * @return QuoteItem
     */
    private function makeItem(
        string $productId,
        string $name,
        float $price,
        string $sku,
        int $qty,
        string $productType = 'simple',
        $childProductId = null
    ): QuoteItem {
        $option = null;
        if ($childProductId !== null) {
            $child = $this->createMock(Product::class);
            $child->method('getId')->willReturn($childProductId);

            $option = $this->getMockBuilder(Option::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getProduct'])
                ->getMock();
            $option->method('getProduct')->willReturn($child);
        }

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                ['getProductType', 'getOptionByCode', 'getProduct', 'getName', 'getPrice', 'getSku', 'getQty']
            )
            ->addMethods(['getProductId'])
            ->getMock();
        $item->method('getProductId')->willReturn($productId);
        $item->method('getProductType')->willReturn($productType);
        $item->method('getOptionByCode')->willReturn($option);
        $item->method('getProduct')->willReturn(null);
        $item->method('getName')->willReturn($name);
        $item->method('getPrice')->willReturn($price);
        $item->method('getSku')->willReturn($sku);
        $item->method('getQty')->willReturn($qty);

        return $item;
    }

    private function makeBlock(array $items, float $grandTotal, string $quoteId, string $currency = 'USD'): Cart
    {
        // getId & getAllVisibleItems are declared on Quote; getGrandTotal is magic.
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems'])
            ->addMethods(['getGrandTotal'])
            ->getMock();
        $quote->method('getId')->willReturn($quoteId);
        $quote->method('getGrandTotal')->willReturn($grandTotal);
        $quote->method('getAllVisibleItems')->willReturn($items);

        $store = $this->createMock(Store::class);
        $store->method('getCurrentCurrencyCode')->willReturn($currency);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $checkoutSession = $this->createMock(CheckoutSession::class);
        $checkoutSession->method('getQuote')->willReturn($quote);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->method('getStoreManager')->willReturn($storeManager);

        // getCartData() reads the media base URL; without this the block hits
        // getBaseUrl() on null.
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn('https://example.com/media/');
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $helper = $this->createMock(MailChimpHelper::class);

        return new Cart($context, $helper, $checkoutSession, new LeafProductIdResolver());
    }

    public function testGetCartDataStructure(): void
    {
        $item   = $this->makeItem('10', 'Blue Widget', 29.99, 'WIDG-BLU', 2);
        $block  = $this->makeBlock([$item], 59.98, '42', 'EUR');
        $result = $block->getCartData();

        $this->assertSame('42', $result['id']);
        $this->assertSame(59.98, $result['totalPrice']);
        $this->assertSame('EUR', $result['currency']);
        $this->assertCount(1, $result['lineItems']);
    }

    public function testGetCartDataLineItemFields(): void
    {
        $item     = $this->makeItem('10', 'Blue Widget', 29.99, 'WIDG-BLU', 2);
        $block    = $this->makeBlock([$item], 59.98, '42');
        $lineItem = $block->getCartData()['lineItems'][0];

        $this->assertSame('10', $lineItem['item']['id']);
        $this->assertSame('Blue Widget', $lineItem['item']['title']);
        $this->assertSame(29.99, $lineItem['item']['price']);
        $this->assertSame('WIDG-BLU', $lineItem['item']['sku']);
        $this->assertSame(2, $lineItem['quantity']);
        $this->assertSame(59.98, $lineItem['price']); // price * qty
    }

    public function testGetCartDataWithEmptyCart(): void
    {
        $block  = $this->makeBlock([], 0.0, '99');
        $result = $block->getCartData();

        $this->assertSame([], $result['lineItems']);
        $this->assertSame(0.0, $result['totalPrice']);
    }

    public function testGetCartDataLineItemPriceIsQtyMultiplied(): void
    {
        $item     = $this->makeItem('5', 'Shoe', 50.0, 'SHOE-1', 3);
        $block    = $this->makeBlock([$item], 150.0, '1');
        $lineItem = $block->getCartData()['lineItems'][0];

        $this->assertSame(150.0, $lineItem['price']); // 50 * 3
    }

    public function testGetCartDataConfigurableSendsChosenChildAsVariantId(): void
    {
        $item     = $this->makeItem('62', 'Chaz Kangeroo Hoodie', 52.0, 'MH01-XS-Black', 1, 'configurable', 47);
        $block    = $this->makeBlock([$item], 52.0, '13');
        $lineItem = $block->getCartData()['lineItems'][0];

        $this->assertSame('47', $lineItem['item']['id'], 'id must be the chosen child simple');
        $this->assertSame('62', $lineItem['item']['productId'], 'productId must stay the parent');
    }

    public function testGetCartDataConfigurableWithoutResolvableChildFallsBackToParent(): void
    {
        $item     = $this->makeItem('62', 'Chaz Kangeroo Hoodie', 52.0, 'MH01', 1, 'configurable', null);
        $block    = $this->makeBlock([$item], 52.0, '13');
        $lineItem = $block->getCartData()['lineItems'][0];

        $this->assertSame('62', $lineItem['item']['id']);
        $this->assertSame('62', $lineItem['item']['productId']);
    }
}
