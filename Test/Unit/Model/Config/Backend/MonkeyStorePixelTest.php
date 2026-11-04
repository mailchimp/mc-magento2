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
use Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner;
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
 */
class MonkeyStorePixelTest extends TestCase
{
    /** @var array  each provision() call, as [storeId, mailchimpStoreId, domain, boundSiteOnly] */
    private $provisioned = [];

    /** @var string[] */
    private $logged = [];

    /**
     * @param  bool  $justConnected
     * @param  int   $existingPixelRows
     * @param  string $scope
     * @param  \Throwable|null $provisionThrows
     * @return MonkeyStore
     */
    private function backend(
        bool $justConnected,
        int $existingPixelRows = 0,
        string $scope = 'stores',
        $provisionThrows = null
    ): MonkeyStore {
        $this->provisioned = [];
        $this->logged = [];

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('log')->willReturnCallback(function ($message) {
            $this->logged[] = (string)$message;
        });

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->method('provision')->willReturnCallback(
            function ($storeId, $mailchimpStoreId, $domain, $boundSiteOnly = false) use ($provisionThrows) {
                $this->provisioned[] = [$storeId, $mailchimpStoreId, $domain, $boundSiteOnly];
                if ($provisionThrows !== null) {
                    throw $provisionThrows;
                }
            }
        );

        $collection = $this->createMock(ConfigDataCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn($existingPixelRows);
        $collectionFactory = $this->createMock(ConfigDataCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $store = new class {
            public function getBaseUrl()
            {
                return 'https://shop.example/en/';
            }
        };
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStore')->willReturn($store);

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
        $this->inject($backend, MonkeyStore::class, 'connectedSiteProvisioner', $provisioner);
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

    public function testAConnectionProvisionsTheBoundSiteOnly(): void
    {
        $backend = $this->backend(true);

        $backend->afterSave();

        $this->assertCount(1, $this->provisioned);
        $this->assertSame(
            [7, 'mc-store-id', 'shop.example', true],
            $this->provisioned[0],
            'the connection path must ask for the bound site only'
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

        $this->assertSame([], $this->provisioned);
    }

    public function testAStoreViewThatWasAlreadyConnectedIsLeftAlone(): void
    {
        $backend = $this->backend(false);

        $backend->afterSave();

        $this->assertSame([], $this->provisioned);
    }

    /**
     * The Pixel is a store-view setting, so a connection made at default or
     * website scope has no row to write and nothing to provision.
     */
    public function testAConnectionWiderThanAStoreViewProvisionsNothing(): void
    {
        $backend = $this->backend(true, 0, 'default');

        $backend->afterSave();

        $this->assertSame([], $this->provisioned);
    }

    /**
     * A provisioning failure must not fail the connection: this runs inside the
     * save of the field that connects the store.
     */
    public function testAFailureIsLoggedAndNeverFailsTheConnection(): void
    {
        $backend = $this->backend(true, 0, 'stores', new \RuntimeException('mailchimp is down'));

        $backend->afterSave();

        $this->assertCount(1, $this->provisioned);
        $this->assertNotEmpty($this->logged);
        $this->assertStringContainsString('could not provision the Pixel', $this->logged[0]);
    }

    /**
     * One connection, one attempt: a second save after the first must not
     * provision again.
     */
    public function testTheFlagIsConsumed(): void
    {
        $backend = $this->backend(true);

        $backend->afterSave();
        $backend->afterSave();

        $this->assertCount(1, $this->provisioned);
    }
}
