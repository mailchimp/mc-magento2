<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Test\Unit\Model\Api;

use Ebizmarts\MailChimp\Model\Api\Product;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class ProductCategoriesTest extends TestCase
{
    /** @var array */
    private $lookups = [];

    private function makeApi(array $responses, $batchInvocations = 0)
    {
        $this->lookups = [];
        $create = function () use (&$responses) {
            $names = (count($responses) > 1 ? array_shift($responses) : reset($responses)) ?: [];
            $collection = new class($names) implements \IteratorAggregate {
                /** @var int */
                public $storeId;
                /** @var array */
                public $attributes;
                /** @var array */
                public $filters = [];
                /** @var array */
                public $sort;
                /** @var array */
                private $names;

                public function __construct(array $names)
                {
                    $this->names = $names;
                }
                public function addAttributeToSelect($attributes)
                {
                    $this->attributes = $attributes;
                    return $this;
                }
                public function setStoreId($storeId)
                {
                    $this->storeId = $storeId;
                    return $this;
                }
                public function addAttributeToFilter($attribute, $condition)
                {
                    $this->filters[$attribute] = $condition;
                    return $this;
                }
                public function addAttributeToSort($attribute, $direction)
                {
                    $this->sort = [$attribute, $direction];
                    return $this;
                }
                public function getIterator(): \Traversable
                {
                    return new \ArrayIterator(array_map(function ($name) {
                        return new DataObject(['name' => $name]);
                    }, $this->names));
                }
            };
            $this->lookups[] = $collection;
            return $collection;
        };
        $factory = new class($create) {
            /** @var callable */
            private $create;
            public function __construct($create)
            {
                $this->create = $create;
            }
            public function create()
            {
                return ($this->create)();
            }
        };

        $api = $this->getMockBuilder(Product::class)->disableOriginalConstructor()
            ->onlyMethods(['_markSpecialPrices', 'processDeletedProducts', '_getCollection'])->getMock();
        $api->expects($this->exactly($batchInvocations))->method('processDeletedProducts')->willReturn([]);
        $collection = new class implements \IteratorAggregate {
            public function addFieldToFilter()
            {
                return $this;
            }
            public function addStoreFilter()
            {
                return $this;
            }
            public function getSelect()
            {
                return $this;
            }
            public function reset()
            {
                return $this;
            }
            public function columns()
            {
                return $this;
            }
            public function joinLeft()
            {
                return $this;
            }
            public function where()
            {
                return $this;
            }
            public function limit()
            {
                return $this;
            }
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }
        };
        $api->method('_getCollection')->willReturn($collection);
        $helper = new class {
            public function getConfigValue()
            {
                return 'store';
            }
            public function getTableName()
            {
                return 'mailchimp_sync_ecommerce';
            }
            public function getGmtTimeStamp()
            {
                return 1;
            }
        };
        foreach (['_categoryCollection' => $factory, '_helper' => $helper] as $name => $value) {
            $property = new \ReflectionProperty(Product::class, $name);
            if (PHP_VERSION_ID < 80100) {
                $property->setAccessible(true);
            }
            $property->setValue($api, $value);
        }
        return $api;
    }

    private function categories(Product $api, $ids, $storeId = 1)
    {
        $product = $this->getMockBuilder(CatalogProduct::class)->disableOriginalConstructor()
            ->onlyMethods(['getCategoryIds'])->getMock();
        $product->expects($this->once())->method('getCategoryIds')->willReturn($ids);
        $method = new \ReflectionMethod(Product::class, 'getProductCategories');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        return $method->invoke($api, $product, $storeId);
    }

    public function testOneLookupForOneHundredProductsSharingCategories()
    {
        $api = $this->makeApi([['Root', 'Shoes']]);
        for ($i = 0; $i < 100; $i++) {
            $this->assertSame('Root - Shoes', $this->categories($api, [2, 7]));
        }
        $this->assertCount(1, $this->lookups);
        $this->assertSame(['name'], $this->lookups[0]->attributes);
        $this->assertSame(1, $this->lookups[0]->storeId);
        $this->assertSame(
            ['is_active' => ['eq' => '1'], 'entity_id' => ['in' => [2, 7]]],
            $this->lookups[0]->filters
        );
        $this->assertSame(['level', 'asc'], $this->lookups[0]->sort);
    }

    public function testStoreAndDifferentIdsHaveSeparateResults()
    {
        $api = $this->makeApi([['Shoes', 'Sale'], ['Chaussures', 'Soldes'], ['Other']]);
        $this->assertSame('Shoes - Sale', $this->categories($api, [7, 8]));
        $this->assertSame('Chaussures - Soldes', $this->categories($api, [7, 8], 2));
        $this->assertSame('Other', $this->categories($api, [9]));
        $this->assertSame('Shoes - Sale', $this->categories($api, [7, 8]));
        $this->assertCount(3, $this->lookups);
        $this->assertSame(2, $this->lookups[1]->storeId);
    }

    public function testReorderedAndStringIdsReuseTheSameCategories()
    {
        $api = $this->makeApi([['Shoes', 'Sale']]);
        $this->assertSame('Shoes - Sale', $this->categories($api, [7, 8]));
        $this->assertSame('Shoes - Sale', $this->categories($api, [8, 7]));
        $this->assertSame('Shoes - Sale', $this->categories($api, ['8', '7']));
        $this->assertCount(1, $this->lookups);
    }

    public function testNoMatchingActiveCategoriesIsCached()
    {
        $api = $this->makeApi([[]]);
        $this->assertSame('None', $this->categories($api, [7]));
        $this->assertSame('None', $this->categories($api, [7]));
        $this->assertCount(1, $this->lookups);
    }

    public function testEmptyOrNonArrayIdsStillReturnNullWithoutLookup()
    {
        $api = $this->makeApi([]);
        foreach ([[], null, false, '7'] as $ids) {
            $this->assertNull($this->categories($api, $ids));
        }
        $this->assertCount(0, $this->lookups);
    }

    public function testBatchInvocationRefreshesCategoriesEvenForTheSameStore()
    {
        $api = $this->makeApi([['Old name'], ['New name']], 2);
        $api->_sendProducts(1);
        $this->assertSame('Old name', $this->categories($api, [7]));
        $api->_sendProducts(1);
        $this->assertSame('New name', $this->categories($api, [7]));
        $this->assertCount(2, $this->lookups);
    }

    public function testOrderInvocationRefreshesCategories()
    {
        $api = $this->makeApi([['Old name'], ['New name']]);
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->expects($this->exactly(2))->method('getAllVisibleItems')->willReturn([]);
        $api->sendModifiedProduct($order, 'store', 1);
        $this->assertSame('Old name', $this->categories($api, [7]));
        $api->sendModifiedProduct($order, 'store', 1);
        $this->assertSame('New name', $this->categories($api, [7]));
        $this->assertCount(2, $this->lookups);
    }

    public function testQuoteInvocationRefreshesCategories()
    {
        $api = $this->makeApi([['Old name'], ['New name']]);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->exactly(2))->method('getAllVisibleItems')->willReturn([]);
        $api->sendQuoteModifiedProduct($quote, 'store', 1);
        $this->assertSame('Old name', $this->categories($api, [7]));
        $api->sendQuoteModifiedProduct($quote, 'store', 1);
        $this->assertSame('New name', $this->categories($api, [7]));
        $this->assertCount(2, $this->lookups);
    }
}
