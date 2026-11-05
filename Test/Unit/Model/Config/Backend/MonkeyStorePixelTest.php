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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Config\Backend;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Config\Backend\MonkeyStore;
use Ebizmarts\MailChimp\Model\Service\Pixel\NewConnectionQueue;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * Turning the Pixel on when a store view connects.
 *
 * Mailchimp asks for the Pixel to be active on every new connection, which puts
 * three rules in one place: only a connection provisions, an explicit No is
 * never overridden, and only a connected site bound to the Mailchimp store is
 * used -- a standalone one cannot attribute, so on a shared host the Pixel stays
 * off instead of being switched on and attributing to nothing.
 *
 * The backend model only decides and queues. It runs before Magento
 * re-initialises the configuration, so provisioning happens in ConfigObserver,
 * which has its own tests for that half (ConfigObserverPixelTest). That half
 * also drops queued store views that the save did not end up connecting.
 *
 * Each test runs the backend model's own beforeSave() and afterSave(), against
 * this install unless it says otherwise:
 *
 *   store view 1 and 2 on website 1, store view 3 on website 2.
 */
class MonkeyStorePixelTest extends TestCase
{
    /** @var NewConnectionQueue */
    private $queue;

    /** @var int[]  store view id => website id */
    private const STORES = [1 => 1, 2 => 1, 3 => 2];

    /**
     * Save the Mailchimp store field at a scope, as Magento's Config::save()
     * would: beforeSave(), then afterSave().
     *
     * @param  string $scope            'default', 'websites' or 'stores'
     * @param  int    $scopeId
     * @param  int[]  $connectedBefore  store views connected before the save
     * @param  array  $pixelRows        enabled_for_store rows, as [scope, scope_id]
     * @param  string $value            the submitted Mailchimp store
     * @return MonkeyStore
     */
    private function save(
        string $scope,
        int $scopeId,
        array $connectedBefore = [],
        array $pixelRows = [],
        string $value = 'mc-store-id'
    ): MonkeyStore {
        $backend = $this->backend($scope, $scopeId, $connectedBefore, $pixelRows, $value);

        $backend->beforeSave();
        $backend->afterSave();

        return $backend;
    }

    private function backend(
        string $scope,
        int $scopeId,
        array $connectedBefore,
        array $pixelRows,
        string $value
    ): MonkeyStore {
        $this->queue = new NewConnectionQueue();

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isStoreConnected')->willReturnCallback(function ($storeId) use ($connectedBefore) {
            return in_array((int)$storeId, $connectedBefore, true);
        });

        $rows = array_map(function ($row) {
            return ['scope' => $row[0], 'scope_id' => (string)$row[1], 'value' => '0'];
        }, $pixelRows);
        $collection = $this->createMock(ConfigDataCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getData')->willReturn($rows);
        $collectionFactory = $this->createMock(ConfigDataCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $stores = [];
        foreach (self::STORES as $storeId => $websiteId) {
            $store = $this->createMock(\Magento\Store\Model\Store::class);
            $store->method('getWebsiteId')->willReturn($websiteId);
            $stores[$storeId] = $store;
        }
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn($stores);
        $storeManager->method('getStore')->willReturnCallback(function ($storeId) use ($stores) {
            return $stores[$storeId];
        });

        $backend = new class ($scope, $scopeId) extends MonkeyStore {
            /** @var string */
            private $theScope;

            /** @var int */
            private $theScopeId;

            public function __construct($scope, $scopeId)
            {
                $this->theScope = $scope;
                $this->theScopeId = $scopeId;
            }

            public function getScope()
            {
                return $this->theScope;
            }

            public function getScopeId()
            {
                return $this->theScopeId;
            }

            public function isValueChanged()
            {
                return false;
            }
        };

        $backend->setValue($value);

        $this->inject($backend, MonkeyStore::class, '_helper', $helper);
        $this->inject($backend, MonkeyStore::class, '_storeManager', $storeManager);
        $this->inject($backend, MonkeyStore::class, 'newConnectionQueue', $this->queue);
        $this->inject($backend, MonkeyStore::class, 'configDataCollectionFactory', $collectionFactory);
        $this->inject($backend, \Magento\Framework\App\Config\Value::class, 'cacheTypeList', $this->createMock(TypeListInterface::class));
        $this->inject($backend, AbstractModel::class, '_eventManager', $this->createMock(ManagerInterface::class));

        return $backend;
    }

    private function inject($object, $class, $property, $value): void
    {
        $p = new \ReflectionProperty($class, $property);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    // ── Store-view scope ─────────────────────────────────────────────────────

    public function testAStoreViewConnectionQueuesThatStoreView(): void
    {
        $this->save('stores', 2);

        $this->assertSame([2], $this->queue->drain());
    }

    public function testAStoreViewThatWasAlreadyConnectedIsLeftAlone(): void
    {
        $this->save('stores', 2, [2]);

        $this->assertSame([], $this->queue->drain());
    }

    /**
     * The rule that outranks default-on: a value chosen by someone is never
     * overridden, and after the toggle was locked until connection a row is
     * exactly that.
     */
    public function testAStoreViewWithAChosenValueIsLeftAlone(): void
    {
        $this->save('stores', 2, [], [['stores', 2]]);

        $this->assertSame([], $this->queue->drain());
    }

    // ── Default scope ────────────────────────────────────────────────────────

    /**
     * The gap this closes: a connection at default scope -- how every
     * single-store install connects -- used to queue nothing at all.
     */
    public function testADefaultConnectionQueuesEveryStoreViewNotYetConnected(): void
    {
        $this->save('default', 0, [2]);

        $this->assertSame([1, 3], $this->queue->drain());
    }

    public function testADefaultSaveWithEveryStoreViewConnectedQueuesNothing(): void
    {
        $this->save('default', 0, [1, 2, 3]);

        $this->assertSame([], $this->queue->drain());
    }

    // ── Website scope ────────────────────────────────────────────────────────

    public function testAWebsiteConnectionQueuesOnlyItsOwnStoreViews(): void
    {
        $this->save('websites', 1);

        $this->assertSame([1, 2], $this->queue->drain());
    }

    // ── A choice at any scope that reaches the store view ───────────────────

    public function testAStoreViewRowIsAChoiceForThatStoreViewOnly(): void
    {
        $this->save('default', 0, [], [['stores', 1]]);

        $this->assertSame([2, 3], $this->queue->drain());
    }

    /**
     * Single-store mode saves the store-view-only toggle at website scope, so a
     * merchant's No on such an install is a website row.
     */
    public function testAWebsiteRowIsAChoiceForThatWebsitesStoreViews(): void
    {
        $this->save('default', 0, [], [['websites', 1]]);

        $this->assertSame([3], $this->queue->drain());
    }

    public function testADefaultRowIsAChoiceForEveryStoreView(): void
    {
        $this->save('default', 0, [], [['default', 0]]);

        $this->assertSame([], $this->queue->drain());
    }

    // ── What is not a connection ─────────────────────────────────────────────

    /**
     * The dropdown's two non-answers (-1, its placeholder, and an empty value)
     * are not a Mailchimp store, so saving them connects nothing.
     */
    public function testANonStoreValueQueuesNothing(): void
    {
        $this->save('default', 0, [], [], '-1');
        $this->assertSame([], $this->queue->drain());

        $this->save('stores', 2, [], [], '');
        $this->assertSame([], $this->queue->drain());
    }

    /**
     * afterSave() runs before Magento re-initialises the configuration, so it
     * must not provision: the provisioner would read the API key from before
     * this save. The backend model has no provisioner to call at all.
     */
    public function testTheBackendModelNeverProvisionsItself(): void
    {
        $constructor = new \ReflectionMethod(MonkeyStore::class, '__construct');
        $types = array_map(function (\ReflectionParameter $parameter) {
            $type = $parameter->getType();
            return $type instanceof \ReflectionNamedType ? $type->getName() : '';
        }, $constructor->getParameters());

        $this->assertNotContains(
            \Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner::class,
            $types
        );
    }

    /**
     * One save, one attempt: what beforeSave() found is consumed by the first
     * afterSave().
     */
    public function testTheStoreViewsFoundBeforeTheSaveAreConsumed(): void
    {
        $backend = $this->save('default', 0);
        $this->assertSame([1, 2, 3], $this->queue->drain());

        $backend->afterSave();

        $this->assertSame([], $this->queue->drain());
    }
}
