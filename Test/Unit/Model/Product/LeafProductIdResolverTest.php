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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Product;

use Ebizmarts\MailChimp\Model\Product\LeafProductIdResolver;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class LeafProductIdResolverTest extends TestCase
{
    /**
     * @var LeafProductIdResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new LeafProductIdResolver();
    }

    /**
     * @param  string      $productType
     * @param  int         $productId
     * @param  Option|null $simpleOption
     * @return QuoteItem
     */
    private function quoteItem(string $productType, int $productId, $simpleOption = null): QuoteItem
    {
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getOptionByCode'])
            ->addMethods(['getProductId'])
            ->getMock();
        $item->method('getProductType')->willReturn($productType);
        $item->method('getProductId')->willReturn($productId);
        $item->method('getOptionByCode')->willReturn($simpleOption);

        return $item;
    }

    /**
     * @param  int|null $childProductId
     * @return Option
     */
    private function simpleOption($childProductId): Option
    {
        $product = null;
        if ($childProductId !== null) {
            $product = $this->createMock(Product::class);
            $product->method('getId')->willReturn($childProductId);
        }

        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProduct'])
            ->getMock();
        $option->method('getProduct')->willReturn($product);

        return $option;
    }

    public function testQuoteItemSimpleReturnsOwnId(): void
    {
        $this->assertSame(10, $this->resolver->forQuoteItem($this->quoteItem('simple', 10)));
    }

    public function testQuoteItemBundleReturnsOwnId(): void
    {
        $this->assertSame(45, $this->resolver->forQuoteItem($this->quoteItem('bundle', 45)));
    }

    public function testQuoteItemConfigurableReturnsChildId(): void
    {
        $item = $this->quoteItem('configurable', 62, $this->simpleOption(47));
        $this->assertSame(47, $this->resolver->forQuoteItem($item));
    }

    public function testQuoteItemConfigurableWithoutOptionFallsBackToParent(): void
    {
        $item = $this->quoteItem('configurable', 62, null);
        $this->assertSame(62, $this->resolver->forQuoteItem($item));
    }

    public function testQuoteItemConfigurableWithOptionButNoProductFallsBackToParent(): void
    {
        $item = $this->quoteItem('configurable', 62, $this->simpleOption(null));
        $this->assertSame(62, $this->resolver->forQuoteItem($item));
    }

    /**
     * @param  string $productType
     * @param  array  $children
     * @return OrderItem
     */
    private function orderItem(string $productType, array $children = []): OrderItem
    {
        $item = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getChildrenItems'])
            ->getMock();
        $item->method('getProductType')->willReturn($productType);
        $item->method('getChildrenItems')->willReturn($children);

        return $item;
    }

    /**
     * @param  int $productId
     * @return OrderItem
     */
    private function childOrderItem(int $productId): OrderItem
    {
        $child = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductId'])
            ->getMock();
        $child->method('getProductId')->willReturn($productId);

        return $child;
    }

    public function testOrderItemSimpleReturnsNull(): void
    {
        $this->assertNull($this->resolver->forOrderItem($this->orderItem('simple')));
    }

    public function testOrderItemConfigurableReturnsChildId(): void
    {
        $item = $this->orderItem('configurable', [$this->childOrderItem(47)]);
        $this->assertSame(47, $this->resolver->forOrderItem($item));
    }

    public function testOrderItemConfigurableWithoutChildrenReturnsNull(): void
    {
        $this->assertNull($this->resolver->forOrderItem($this->orderItem('configurable', [])));
    }

    public function testOrderItemSkipsChildrenWithoutProductId(): void
    {
        $item = $this->orderItem('configurable', [$this->childOrderItem(0), $this->childOrderItem(47)]);
        $this->assertSame(47, $this->resolver->forOrderItem($item));
    }
}
