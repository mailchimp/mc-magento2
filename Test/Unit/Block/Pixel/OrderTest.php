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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order as SalesOrder;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    /**
     * Uses DataObject to avoid fighting magic-getter mocking on OrderItem.
     */
    private function makeItem(
        string $productId,
        string $name,
        float $price,
        string $sku,
        float $qtyOrdered,
        float $rowTotal
    ): DataObject {
        return new DataObject([
            'product_id'   => $productId,
            'name'         => $name,
            'price'        => $price,
            'sku'          => $sku,
            'qty_ordered'  => $qtyOrdered,
            'row_total'    => $rowTotal,
        ]);
    }

    private function makeBlock(?SalesOrder $order): Order
    {
        $checkoutSession = $this->createMock(CheckoutSession::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $helper = $this->createMock(MailChimpHelper::class);

        return new Order($context, $helper, $checkoutSession);
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
}
