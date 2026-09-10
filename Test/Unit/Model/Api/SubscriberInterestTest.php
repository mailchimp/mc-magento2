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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Api;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Api\Subscriber;
use PHPUnit\Framework\TestCase;

/**
 * This object has no shared="false" in di.xml, so one instance serves every
 * store view in a cron run. Anything remembered on it has to be scoped to the
 * view being synced or it bleeds into the next one.
 */
class SubscriberInterestTest extends TestCase
{
    /** @var array  store ids passed to getInterest(), in order */
    private $fetchedFor = [];

    /**
     * @param  array $byStore  store id => interest groups to answer with
     * @return array [$api, $helper]
     */
    private function make(array $byStore)
    {
        $this->fetchedFor = [];

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getInterest')->willReturnCallback(function ($storeId) use ($byStore) {
            $this->fetchedFor[] = $storeId;
            return isset($byStore[$storeId]) ? $byStore[$storeId] : [];
        });
        $helper->method('getSubscriberInterest')->willReturnCallback(
            function ($subscriberId, $storeId, $interest = null) {
                return is_array($interest) ? $interest : [];
            }
        );

        $helper->method('getTableName')->willReturn('mailchimp_sync_ecommerce');
        $helper->method('getDateMicrotime')->willReturn('1757000000');

        // Enough of a collection for sendSubscribers() to walk it and find
        // nothing. An empty view is the case that matters here: it must still
        // reset, and it must still cost no fetch.
        $select = new class {
            public function joinLeft() { return $this; }
            public function where() { return $this; }
            public function limit() { return $this; }
        };
        $collection = new class($select) implements \IteratorAggregate {
            private $select;
            public function __construct($select) { $this->select = $select; }
            public function addFieldToFilter() { return $this; }
            public function getSelect() { return $this->select; }
            public function getIterator(): \Traversable { return new \ArrayIterator([]); }
        };
        $collectionFactory = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['create'])
            ->getMock();
        $collectionFactory->method('create')->willReturn($collection);

        $api = $this->getMockBuilder(Subscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        foreach (['_helper' => $helper, '_subscriberCollection' => $collectionFactory] as $name => $value) {
            $prop = new \ReflectionProperty(Subscriber::class, $name);
            $prop->setAccessible(true);
            $prop->setValue($api, $value);
        }

        return [$api, $helper];
    }

    /**
     * Begins a store view the way the cron does — by calling
     * sendSubscribers() — rather than by resetting the two fields directly.
     *
     * That distinction is the whole point: resetting them here would make the
     * cross-view test pass even if the reset were deleted from the module, so
     * it would be pinning the test helper rather than the code.
     *
     * @param  Subscriber $api
     * @param  int        $storeId
     * @return void
     */
    private function beginStore(Subscriber $api, $storeId)
    {
        $api->sendSubscribers($storeId, 'list-' . $storeId);
    }

    /**
     * @param  Subscriber $api
     * @param  int        $storeId
     * @return void
     */
    private function syncSubscriber(Subscriber $api, $storeId)
    {
        // addMethods, not onlyMethods: these are magic getters on the Magento
        // model rather than declared methods.
        $subscriber = $this->getMockBuilder(\Magento\Newsletter\Model\Subscriber::class)
            ->disableOriginalConstructor()
            ->addMethods(['getSubscriberId', 'getStoreId'])
            ->getMock();
        $subscriber->method('getSubscriberId')->willReturn(7);
        $subscriber->method('getStoreId')->willReturn($storeId);

        $m = new \ReflectionMethod(Subscriber::class, '_getInterest');
        $m->setAccessible(true);
        $m->invoke($api, $subscriber);
    }

    /**
     * The whole point: one fetch for a store view however many subscribers it
     * has, instead of one per subscriber.
     */
    public function testTheGroupsAreFetchedOncePerStoreViewNotPerSubscriber()
    {
        list($api) = $this->make([1 => []]);

        $this->beginStore($api, 1);
        for ($i = 0; $i < 5; $i++) {
            $this->syncSubscriber($api, 1);
        }

        $this->assertSame([1], $this->fetchedFor);
    }

    /**
     * A store view with nothing to sync never reaches _getInterest, so it now
     * costs nothing at all. Before, the fetch happened before the collection
     * was even built.
     */
    public function testAStoreViewWithNoSubscribersFetchesNothing()
    {
        list($api) = $this->make([1 => []]);

        $this->beginStore($api, 1);

        $this->assertSame([], $this->fetchedFor);
    }

    /**
     * The trap: this object is a DI singleton reused across store views. A
     * loaded-flag that is not reset would serve view 1's groups to view 2 --
     * wrong groups synced, no error, no log line.
     */
    public function testOneStoreViewDoesNotInheritAnothersGroups()
    {
        list($api) = $this->make([
            1 => ['cat-a' => ['category' => []]],
            2 => ['cat-b' => ['category' => []]],
        ]);

        $this->beginStore($api, 1);
        $this->syncSubscriber($api, 1);

        $this->beginStore($api, 2);
        $this->syncSubscriber($api, 2);

        $this->assertSame([1, 2], $this->fetchedFor);

        $prop = new \ReflectionProperty(Subscriber::class, '_interest');
        $prop->setAccessible(true);
        $this->assertSame(['cat-b' => ['category' => []]], $prop->getValue($api));
    }

    /**
     * An empty result is a result. Without the separate flag, a store with no
     * groups configured would look unfetched on every subscriber.
     */
    public function testAnEmptyResultIsNotRefetched()
    {
        list($api) = $this->make([1 => []]);

        $this->beginStore($api, 1);
        $this->syncSubscriber($api, 1);
        $this->syncSubscriber($api, 1);
        $this->syncSubscriber($api, 1);

        $this->assertSame([1], $this->fetchedFor);
    }
}
