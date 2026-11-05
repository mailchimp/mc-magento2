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
 * which has its own tests for that half (ConfigObserverPixelTest).
 */
class MonkeyStorePixelTest extends TestCase
{
    /** @var NewConnectionQueue */
    private $queue;

    /**
     * @param  bool  $justConnected
     * @param  int   $existingPixelRows
     * @param  string $scope
     * @return MonkeyStore
     */
    private function backend(
        bool $justConnected,
        int $existingPixelRows = 0,
        string $scope = 'stores'
    ): MonkeyStore {
        $this->queue = new NewConnectionQueue();

        $helper = $this->createMock(MailChimpHelper::class);

        $collection = $this->createMock(ConfigDataCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn($existingPixelRows);
        $collectionFactory = $this->createMock(ConfigDataCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $storeManager = $this->createMock(StoreManager::class);

        $backend = new class ($scope) extends MonkeyStore {
            /** @var string */
            private $theScope;

            public function __construct($scope)
            {
                $this->theScope = $scope;
            }

            public function getScope()
            {
                return $this->theScope;
            }

            public function getScopeId()
            {
                return 7;
            }

            public function isValueChanged()
            {
                return false;
            }
        };

        $backend->setValue('mc-store-id');

        $this->inject($backend, MonkeyStore::class, '_helper', $helper);
        $this->inject($backend, MonkeyStore::class, '_storeManager', $storeManager);
        $this->inject($backend, MonkeyStore::class, 'newConnectionQueue', $this->queue);
        $this->inject($backend, MonkeyStore::class, 'configDataCollectionFactory', $collectionFactory);
        $this->inject($backend, MonkeyStore::class, 'justConnected', $justConnected);
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

    public function testAConnectionQueuesTheStoreView(): void
    {
        $backend = $this->backend(true);

        $backend->afterSave();

        $this->assertSame([7], $this->queue->drain());
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
     * The rule that outranks default-on: a value chosen by someone is never
     * overridden, and after the toggle was locked until connection a row is
     * exactly that.
     */
    public function testAStoreViewWithAChosenValueIsLeftAlone(): void
    {
        $backend = $this->backend(true, 1);

        $backend->afterSave();

        $this->assertSame([], $this->queue->drain());
    }

    public function testAStoreViewThatWasAlreadyConnectedIsLeftAlone(): void
    {
        $backend = $this->backend(false);

        $backend->afterSave();

        $this->assertSame([], $this->queue->drain());
    }

    /**
     * The Pixel is a store-view setting, so a connection made at default or
     * website scope has no row to write and nothing to provision.
     */
    public function testAConnectionWiderThanAStoreViewQueuesNothing(): void
    {
        $backend = $this->backend(true, 0, 'default');

        $backend->afterSave();

        $this->assertSame([], $this->queue->drain());
    }

    /**
     * One connection, one attempt: a second save after the first must not
     * queue again.
     */
    public function testTheFlagIsConsumed(): void
    {
        $backend = $this->backend(true);

        $backend->afterSave();
        $backend->afterSave();

        $this->assertSame([7], $this->queue->drain());
    }
}
