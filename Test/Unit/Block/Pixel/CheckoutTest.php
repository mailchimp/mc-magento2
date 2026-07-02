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

use Ebizmarts\MailChimp\Block\Pixel\Checkout;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    /**
     * Builds a quote mock.
     *
     * On Quote: getId & getShippingAddress are declared; the rest are magic.
     */
    private function makeQuote(
        string $id,
        array $items,
        float $subtotal,
        float $grandTotal,
        ?string $couponCode,
        float $tax,
        float $shipping,
        float $discountAmount
    ): \Magento\Quote\Model\Quote {
        $address = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTaxAmount', 'getShippingAmount', 'getDiscountAmount'])
            ->getMock();
        $address->method('getTaxAmount')->willReturn($tax);
        $address->method('getShippingAmount')->willReturn($shipping);
        $address->method('getDiscountAmount')->willReturn(-$discountAmount); // Magento stores as negative

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getSubtotal', 'getGrandTotal', 'getCouponCode'])
            ->getMock();
        $quote->method('getId')->willReturn($id);
        $quote->method('getAllVisibleItems')->willReturn($items);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getSubtotal')->willReturn($subtotal);
        $quote->method('getGrandTotal')->willReturn($grandTotal);
        $quote->method('getCouponCode')->willReturn($couponCode);

        return $quote;
    }

    private function makeBlock(\Magento\Quote\Model\Quote $quote, string $currency = 'USD'): Checkout
    {
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

        return new Checkout($context, $helper, $checkoutSession);
    }

    public function testGetCheckoutDataStructure(): void
    {
        $quote = $this->makeQuote('5', [], 100.0, 115.0, null, 10.0, 5.0, 0.0);
        $block = $this->makeBlock($quote, 'GBP');

        $result = $block->getCheckoutData();

        $this->assertSame('5', $result['id']);
        $this->assertSame('5', $result['cartId']);
        $this->assertSame('GBP', $result['currency']);
        $this->assertSame(100.0, $result['subtotalPrice']);
        $this->assertSame(10.0, $result['totalTax']);
        $this->assertSame(5.0, $result['totalShipping']);
        $this->assertSame(115.0, $result['totalPrice']);
        $this->assertSame([], $result['discounts']);
    }

    public function testGetCheckoutDataIncludesDiscountWhenCouponApplied(): void
    {
        $quote = $this->makeQuote('6', [], 100.0, 90.0, 'SAVE10', 0.0, 0.0, 10.0);
        $block = $this->makeBlock($quote);

        $discounts = $block->getCheckoutData()['discounts'];

        $this->assertCount(1, $discounts);
        $this->assertSame('SAVE10', $discounts[0]['code']);
        $this->assertSame(10.0, $discounts[0]['amount']);
        $this->assertSame('fixed', $discounts[0]['type']);
    }

    public function testGetCheckoutDataNoDiscountsWhenNoCoupon(): void
    {
        $quote = $this->makeQuote('7', [], 50.0, 50.0, null, 0.0, 0.0, 0.0);
        $block = $this->makeBlock($quote);

        $this->assertSame([], $block->getCheckoutData()['discounts']);
    }

    public function testGetCheckoutDataWithLineItems(): void
    {
        $item  = new DataObject([
            'product_id' => '99',
            'name'       => 'Sneaker',
            'price'      => 60.0,
            'sku'        => 'SNKR-1',
            'qty'        => 1,
        ]);
        $quote = $this->makeQuote('8', [$item], 60.0, 60.0, null, 0.0, 0.0, 0.0);
        $block = $this->makeBlock($quote);

        $lineItems = $block->getCheckoutData()['lineItems'];

        $this->assertCount(1, $lineItems);
        $this->assertSame('99', $lineItems[0]['item']['id']);
        $this->assertSame('Sneaker', $lineItems[0]['item']['title']);
        $this->assertSame(60.0, $lineItems[0]['item']['price']);
        $this->assertSame(1, $lineItems[0]['quantity']);
    }
}
