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

namespace Ebizmarts\MailChimp\Test\Unit\Cron;

use Ebizmarts\MailChimp\Cron\Webhook;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * The interest-group tree was fetched at the top of processWebhooks(), which
 * reads its queue two lines later. On a five-minute cron that is the whole tree
 * per store view per run whether or not anything consumes it.
 *
 * Its one consumer is _getGroups(), reached only from a `profile` webhook row
 * carrying a GROUPINGS merge field, so these pin that the work now happens
 * there and happens once.
 */
class WebhookLazyGroupsTest extends TestCase
{
    /** @var int  one per interest-categories or interests GET */
    private $apiCalls = 0;

    /**
     * @param  array $categories  what the audience defines
     * @return object
     */
    private function api(array $categories)
    {
        $test = $this;

        $interests = new class($test) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function getAll($listId, $catId, $a = null, $b = null, $c = null)
            {
                $this->test->countApiCall();

                return ['interests' => [
                    ['id' => 'i-' . $catId, 'name' => 'Group', 'category_id' => $catId],
                ]];
            }
        };

        $category = new class($test, $categories, $interests) {
            private $test;
            private $categories;
            public $interests;
            public function __construct($test, $categories, $interests)
            {
                $this->test = $test;
                $this->categories = $categories;
                $this->interests = $interests;
            }
            public function getAll($listId, $a = null, $b = null, $c = null)
            {
                $this->test->countApiCall();

                return ['categories' => $this->categories];
            }
        };

        $lists = new \stdClass();
        $lists->interestCategory = $category;

        $api = new \stdClass();
        $api->lists = $lists;

        return $api;
    }

    public function countApiCall()
    {
        $this->apiCalls++;
    }

    /**
     * @param  int   $storeViews
     * @param  array $categories
     * @return Webhook
     */
    private function cron($storeViews, array $categories)
    {
        $this->apiCalls = 0;

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('isApiKeyFailed')->willReturn(false);
        $helper->method('getDefaultList')->willReturn('list-1');
        $helper->method('getApi')->willReturnCallback(function () use ($categories) {
            return $this->api($categories);
        });

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')
            ->willReturn(array_fill_keys(range(1, $storeViews), new \stdClass()));

        // An empty queue, built the way processWebhooks() uses it: filtered,
        // limited, then walked.
        $collection = new class implements \IteratorAggregate {
            public function addFieldToFilter($field, $condition)
            {
                return $this;
            }
            public function getSelect()
            {
                return new class {
                    public function limit($n)
                    {
                        return $this;
                    }
                };
            }
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }
        };

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        return new Webhook(
            $helper,
            $this->createMock(SubscriberFactory::class),
            $collectionFactory,
            $this->createMock(MailChimpInterestGroupFactory::class),
            $storeManager,
            $this->createMock(CustomerFactory::class)
        );
    }

    /**
     * @param  Webhook $cron
     * @return array
     */
    private function getGroups(Webhook $cron)
    {
        $method = new \ReflectionMethod(Webhook::class, '_getGroups');
        $method->setAccessible(true);

        return $method->invoke($cron, 'Group', 'cat-1');
    }

    /**
     * The measurement this comes from: five identical cron beacons, 21 `lists`
     * calls each, nothing written and nothing changing. With no row to serve,
     * the run must now cost nothing at all.
     */
    public function testAnEmptyQueueMakesNoApiCalls()
    {
        $cron = $this->cron(4, [['id' => 'cat-1'], ['id' => 'cat-2']]);

        $cron->processWebhooks();

        $this->assertSame(0, $this->apiCalls);
    }

    /**
     * And the consumer still gets its tree, so the laziness did not simply
     * remove the feature.
     */
    public function testTheConsumerStillLoadsTheTree()
    {
        $cron = $this->cron(1, [['id' => 'cat-1']]);

        $groups = $this->getGroups($cron);

        $this->assertSame(2, $this->apiCalls);            // 1 categories + 1 interests
        $this->assertSame(['i-cat-1' => 'i-cat-1'], $groups);
    }

    /**
     * Once per process, not once per row. A hundred profile rows in one queue
     * must not be a hundred trees.
     */
    public function testRepeatedRowsLoadTheTreeOnce()
    {
        $cron = $this->cron(1, [['id' => 'cat-1']]);

        $this->getGroups($cron);
        $this->getGroups($cron);
        $this->getGroups($cron);

        $this->assertSame(2, $this->apiCalls);
    }

    /**
     * The #1450 lesson, one file over: an audience that defines no interest
     * categories legitimately loads as []. A `if (!$this->groups)` guard would
     * read that as "not loaded" and re-fetch for every row that asked, which is
     * the amplification this change exists to remove.
     */
    public function testAnAudienceWithNoCategoriesIsNotRefetchedPerRow()
    {
        $cron = $this->cron(1, []);

        $this->getGroups($cron);
        $this->getGroups($cron);
        $this->getGroups($cron);

        $this->assertSame(1, $this->apiCalls);            // the categories call, once
    }
}
