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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    /**
     * Uses DataObject to avoid fighting magic-getter mocking on QuoteItem.
     */
    private function makeItem(
        string $productId,
        string $name,
        float $price,
        string $sku,
        int $qty
    ): DataObject {
        return new DataObject([
            'product_id' => $productId,
            'name'       => $name,
            'price'      => $price,
            'sku'        => $sku,
            'qty'        => $qty,
        ]);
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

        $helper = $this->createMock(MailChimpHelper::class);

        return new Cart($context, $helper, $checkoutSession);
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
}
