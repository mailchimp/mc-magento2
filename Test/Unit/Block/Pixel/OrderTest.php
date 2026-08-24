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

use Ebizmarts\MailChimp\Block\Pixel\Order;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Product\LeafProductIdResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    /**
     * Real OrderItem mocks: the block hands the item to LeafProductIdResolver,
     * which type-hints OrderItemInterface, so a plain DataObject no longer fits.
     *
     * @param  string   $productId
     * @param  string   $name
     * @param  float    $price
     * @param  string   $sku
     * @param  float    $qtyOrdered
     * @param  float    $rowTotal
     * @param  string   $productType
     * @param  int|null $childProductId
     * @return OrderItem
     */
    private function makeItem(
        string $productId,
        string $name,
        float $price,
        string $sku,
        float $qtyOrdered,
        float $rowTotal,
        string $productType = 'simple',
        $childProductId = null
    ): OrderItem {
        $children = [];
        if ($childProductId !== null) {
            $child = $this->getMockBuilder(OrderItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getProductId'])
                ->getMock();
            $child->method('getProductId')->willReturn($childProductId);
            $children[] = $child;
        }

        $item = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getProductId', 'getProductType', 'getChildrenItems',
                    'getName', 'getPrice', 'getSku', 'getQtyOrdered', 'getRowTotal',
                ]
            )
            ->getMock();
        $item->method('getProductId')->willReturn($productId);
        $item->method('getProductType')->willReturn($productType);
        $item->method('getChildrenItems')->willReturn($children);
        $item->method('getName')->willReturn($name);
        $item->method('getPrice')->willReturn($price);
        $item->method('getSku')->willReturn($sku);
        $item->method('getQtyOrdered')->willReturn($qtyOrdered);
        $item->method('getRowTotal')->willReturn($rowTotal);

        return $item;
    }

    private function makeBlock(?SalesOrder $order): Order
    {
        $checkoutSession = $this->createMock(CheckoutSession::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        // getOrderData() reads the media base URL; without this the block hits
        // getBaseUrl() on null.
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn('https://example.com/media/');
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $helper = $this->createMock(MailChimpHelper::class);

        $product = $this->createMock(Product::class);
        $product->method('getImage')->willReturn('no_selection');
        $product->method('getProductUrl')->willReturn('https://example.com/p.html');

        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->method('getById')->willReturn($product);

        return new Order(
            $context,
            $helper,
            $checkoutSession,
            $productRepository,
            new LeafProductIdResolver()
        );
    }

    // All methods listed here ARE declared on Sales\Order (confirmed via reflection)
    private function makeOrder(
        ?string $id,
        string $incrementId,
        string $currency,
        float $subtotal,
        float $tax,
        float $shipping,
        float $grandTotal,
        array $items,
        bool $isGuest = true,
        ?string $customerId = null
    ): SalesOrder {
        $order = $this->getMockBuilder(SalesOrder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId', 'getIncrementId', 'getOrderCurrencyCode',
                'getSubtotal', 'getTaxAmount', 'getShippingAmount', 'getGrandTotal',
                'getAllVisibleItems', 'getCustomerIsGuest', 'getCustomerId',
            ])
            ->getMock();
        $order->method('getId')->willReturn($id);
        $order->method('getIncrementId')->willReturn($incrementId);
        $order->method('getOrderCurrencyCode')->willReturn($currency);
        $order->method('getSubtotal')->willReturn($subtotal);
        $order->method('getTaxAmount')->willReturn($tax);
        $order->method('getShippingAmount')->willReturn($shipping);
        $order->method('getGrandTotal')->willReturn($grandTotal);
        $order->method('getAllVisibleItems')->willReturn($items);
        $order->method('getCustomerIsGuest')->willReturn($isGuest);
        $order->method('getCustomerId')->willReturn($customerId);

        return $order;
    }

    public function testGetOrderDataReturnsEmptyArrayWhenNoOrderId(): void
    {
        $order = $this->makeOrder(null, '', 'USD', 0, 0, 0, 0, []);
        $block = $this->makeBlock($order);

        $this->assertSame([], $block->getOrderData());
    }

    public function testGetOrderDataStructureForGuestOrder(): void
    {
        $item  = $this->makeItem('20', 'Red Hat', 19.99, 'HAT-RED', 1, 19.99);
        $order = $this->makeOrder('1', '100000042', 'USD', 19.99, 1.60, 5.00, 26.59, [$item]);
        $block = $this->makeBlock($order);

        $result = $block->getOrderData();

        $this->assertSame('100000042', $result['id']);
        $this->assertSame('USD', $result['currency']);
        $this->assertSame(19.99, $result['subtotalPrice']);
        $this->assertSame(1.60, $result['totalTax']);
        $this->assertSame(5.00, $result['totalShipping']);
        $this->assertSame(26.59, $result['totalPrice']);
        $this->assertCount(1, $result['lineItems']);
        $this->assertArrayNotHasKey('customerId', $result);
    }

    public function testGetOrderDataIncludesCustomerIdForLoggedInCustomer(): void
    {
        $order = $this->makeOrder('2', '100000043', 'EUR', 50.0, 4.0, 5.0, 59.0, [], false, '77');
        $block = $this->makeBlock($order);

        $result = $block->getOrderData();

        $this->assertSame('77', $result['customerId']);
    }

    public function testGetOrderDataLineItemFields(): void
    {
        $item  = $this->makeItem('15', 'Blue Jeans', 49.99, 'JEAN-BLU', 2, 99.98);
        $order = $this->makeOrder('3', '100000044', 'USD', 99.98, 8.0, 5.0, 112.98, [$item]);
        $block = $this->makeBlock($order);

        $lineItem = $block->getOrderData()['lineItems'][0];

        $this->assertSame('15', $lineItem['item']['id']);
        $this->assertSame('Blue Jeans', $lineItem['item']['title']);
        $this->assertSame(49.99, $lineItem['item']['price']);
        $this->assertSame('JEAN-BLU', $lineItem['item']['sku']);
        $this->assertSame(2, $lineItem['quantity']);
        $this->assertSame(99.98, $lineItem['price']);
    }

    public function testGetOrderDataNoCustomerIdForGuestWithNullId(): void
    {
        $order = $this->makeOrder('4', '100000045', 'USD', 10.0, 0.0, 0.0, 10.0, [], true, null);
        $block = $this->makeBlock($order);

        $this->assertArrayNotHasKey('customerId', $block->getOrderData());
    }

    public function testGetOrderDataConfigurableSendsChildAsVariantId(): void
    {
        $item  = $this->makeItem('62', 'Chaz Kangeroo Hoodie', 52.0, 'MH01-XS-Black', 1.0, 52.0, 'configurable', 47);
        $order = $this->makeOrder('9', '100000099', 'USD', 52.0, 0.0, 5.0, 57.0, [$item]);
        $block = $this->makeBlock($order);

        $lineItem = $block->getOrderData()['lineItems'][0];

        $this->assertSame('47', $lineItem['item']['id'], 'id must be the child order-item row');
        $this->assertSame('62', $lineItem['item']['productId'], 'productId must stay the parent');
    }

    public function testGetOrderDataConfigurableWithoutChildRowFallsBackToParent(): void
    {
        $item  = $this->makeItem('62', 'Chaz Kangeroo Hoodie', 52.0, 'MH01', 1.0, 52.0, 'configurable', null);
        $order = $this->makeOrder('9', '100000099', 'USD', 52.0, 0.0, 5.0, 57.0, [$item]);
        $block = $this->makeBlock($order);

        $lineItem = $block->getOrderData()['lineItems'][0];

        $this->assertSame('62', $lineItem['item']['id']);
        $this->assertSame('62', $lineItem['item']['productId']);
    }
}
