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

namespace Ebizmarts\MailChimp\Test\Unit\Observer;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner;
use Ebizmarts\MailChimp\Model\Service\Pixel\NewConnectionQueue;
use Ebizmarts\MailChimp\Observer\ConfigObserver;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests the pixel-provisioning logic added to ConfigObserver.
 * The webhook-deletion logic is not tested here — these tests focus only on
 * the provisionPixelIfNeeded() behaviour.
 */
class ConfigObserverPixelTest extends TestCase
{
    private function makeStore(
        int $storeId,
        string $baseUrl = 'https://example.com/'
    ): Store {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_WEB)
            ->willReturn($baseUrl);

        return $store;
    }

    private function makeObserver(
        array $stores,
        array $configMap,
        ConnectedSiteProvisioner $provisioner
    ): ConfigObserver {
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn($stores);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')
            ->willReturnCallback(function (string $path, $storeId) use ($configMap) {
                return $configMap[$storeId][$path] ?? null;
            });
        // Prevent deleteWebHook from being called (old list id is null)
        $helper->method('deleteWebHook');

        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturn(null);

        $reinitableConfig = $this->createMock(ReinitableConfigInterface::class);

        return new ConfigObserver(
            $storeManager,
            $registry,
            $helper,
            $provisioner,
            $reinitableConfig,
            new NewConnectionQueue()
        );
    }

    public function testProvisionIsCalledWhenPixelEnabledAndNotYetProvisioned(): void
    {
        $store = $this->makeStore(1, 'https://shop.example.com/');

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner
            ->expects($this->once())
            ->method('provision')
            ->with(1, 'mc-store-abc', 'shop.example.com');

        $observer = $this->makeObserver(
            [1 => $store],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-abc',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '',  // not yet provisioned
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionIsSkippedWhenPixelAlreadyProvisioned(): void
    {
        $store = $this->makeStore(1);

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->expects($this->never())->method('provision');

        $observer = $this->makeObserver(
            [1 => $store],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-abc',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '<script>/* pixel */</script>',
                    MailChimpHelper::XML_PIXEL_SCRIPT_URL          => 'https://chimpstatic.com/mcjs-connected/js/users/abc/def.js',
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionIsCalledWhenFragmentExistsButUrlIsMissing(): void
    {
        $store = $this->makeStore(1, 'https://shop.example.com/');

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner
            ->expects($this->once())
            ->method('provision')
            ->with(1, 'mc-store-abc', 'shop.example.com');

        $observer = $this->makeObserver(
            [1 => $store],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-abc',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '<script>/* pixel */</script>',
                    MailChimpHelper::XML_PIXEL_SCRIPT_URL          => '',  // URL missing
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionIsSkippedWhenPixelToggleIsOff(): void
    {
        $store = $this->makeStore(1);

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->expects($this->never())->method('provision');

        $observer = $this->makeObserver(
            [1 => $store],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-abc',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '0',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '',
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionIsSkippedWhenStoreNotConnectedToMailchimp(): void
    {
        $store = $this->makeStore(1);

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->expects($this->never())->method('provision');

        $observer = $this->makeObserver(
            [1 => $store],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => null,  // no MC store linked
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '',
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionExceptionIsLoggedAndDoesNotBubble(): void
    {
        $store = $this->makeStore(1, 'https://example.com/');

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->method('provision')
            ->willThrowException(new \Exception('API timeout'));

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')
            ->willReturnCallback(function (string $path) {
                return match ($path) {
                    MailChimpHelper::XML_MAILCHIMP_STORE         => 'mc-store-abc',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT   => '',
                    default                                       => null,
                };
            });
        $helper->expects($this->once())->method('log')->with($this->stringContains('API timeout'));

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn([1 => $store]);

        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturn(null);

        $reinitableConfig = $this->createMock(ReinitableConfigInterface::class);

        $observer = new ConfigObserver(
            $storeManager,
            $registry,
            $helper,
            $provisioner,
            $reinitableConfig,
            new NewConnectionQueue()
        );

        // Must NOT throw
        $observer->execute($this->createMock(EventObserver::class));
    }

    public function testProvisionOnlyCalledForStoresThatNeedIt(): void
    {
        $store1 = $this->makeStore(1, 'https://store1.com/');
        $store2 = $this->makeStore(2, 'https://store2.com/');

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        // Only store 1 needs provisioning (store 2 already has script_fragment)
        $provisioner
            ->expects($this->once())
            ->method('provision')
            ->with(1, 'mc-store-1', 'store1.com');

        $observer = $this->makeObserver(
            [1 => $store1, 2 => $store2],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-1',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '',
                    MailChimpHelper::XML_PIXEL_SCRIPT_URL          => '',
                ],
                2 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE           => 'mc-store-2',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE   => '1',
                    MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT     => '<script>/* already set */</script>',
                    MailChimpHelper::XML_PIXEL_SCRIPT_URL          => 'https://chimpstatic.com/mcjs-connected/js/users/abc/def.js',
                ],
            ],
            $provisioner
        );

        $observer->execute($this->createMock(EventObserver::class));
    }

    // ── Store views connected by this save (queued by MonkeyStore) ───────────

    /** @var array  each provision() call, as [storeId, mailchimpStoreId, domain, boundSiteOnly] */
    private $provisioned = [];

    /** @var string[] */
    private $logged = [];

    /** @var bool */
    private $reinitialised = false;

    /** @var bool[]  whether the config was re-initialised before each provision() call */
    private $provisionedAfterReinit = [];

    /**
     * @param  int[]           $queued      store view ids MonkeyStore queued
     * @param  int[]           $connected   store view ids connected once the save is committed
     * @param  array           $configMap   [storeId => [path => value]] as committed
     * @param  \Throwable|null $provisionThrows
     * @return array{0: ConfigObserver, 1: NewConnectionQueue}
     */
    private function observerForNewConnections(
        array $queued,
        array $connected,
        array $configMap,
        $provisionThrows = null
    ): array {
        $this->provisioned = [];
        $this->logged = [];
        $this->reinitialised = false;
        $this->provisionedAfterReinit = [];

        $stores = [];
        foreach (array_keys($configMap) as $storeId) {
            $stores[$storeId] = $this->makeStore($storeId, 'https://store' . $storeId . '.example/');
        }

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn($stores);
        $storeManager->method('getStore')->willReturnCallback(function ($storeId) use ($stores) {
            return $stores[$storeId];
        });

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')
            ->willReturnCallback(function (string $path, $storeId) use ($configMap) {
                return $configMap[$storeId][$path] ?? null;
            });
        $helper->method('isStoreConnected')->willReturnCallback(function ($storeId) use ($connected) {
            return in_array($storeId, $connected, true);
        });
        $helper->method('log')->willReturnCallback(function ($message) {
            $this->logged[] = (string)$message;
        });

        $provisioner = $this->createMock(ConnectedSiteProvisioner::class);
        $provisioner->method('provision')->willReturnCallback(
            function ($storeId, $mailchimpStoreId, $domain, $boundSiteOnly = false) use ($provisionThrows) {
                $this->provisioned[] = [$storeId, $mailchimpStoreId, $domain, $boundSiteOnly];
                $this->provisionedAfterReinit[] = $this->reinitialised;
                if ($provisionThrows !== null) {
                    throw $provisionThrows;
                }
            }
        );

        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturn(null);

        $reinitableConfig = $this->createMock(ReinitableConfigInterface::class);
        $reinitableConfig->method('reinit')->willReturnCallback(function () use ($reinitableConfig) {
            $this->reinitialised = true;
            return $reinitableConfig;
        });

        $queue = new NewConnectionQueue();
        foreach ($queued as $storeId) {
            $queue->add($storeId);
        }

        return [
            new ConfigObserver($storeManager, $registry, $helper, $provisioner, $reinitableConfig, $queue),
            $queue,
        ];
    }

    public function testANewConnectionIsProvisionedFromTheBoundSiteOnly(): void
    {
        [$observer] = $this->observerForNewConnections(
            [3],
            [3],
            [3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new']]
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertSame([[3, 'mc-store-new', 'store3.example', true]], $this->provisioned);
    }

    /**
     * The reason the provisioning lives here rather than in the backend model:
     * the API key it uses is read through the scope config, which only holds
     * what the merchant just saved once the config has been re-initialised.
     */
    public function testANewConnectionIsProvisionedAfterTheConfigIsReinitialised(): void
    {
        [$observer] = $this->observerForNewConnections(
            [3],
            [3],
            [3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new']]
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertSame([true], $this->provisionedAfterReinit);
    }

    /**
     * The queue is filled inside the save, before the save is known to have
     * stuck. What counts is the committed configuration.
     */
    public function testAQueuedStoreViewThatIsNotConnectedAfterTheSaveIsSkipped(): void
    {
        [$observer] = $this->observerForNewConnections(
            [3],
            [],
            [3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new']]
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertSame([], $this->provisioned);
    }

    /**
     * A save at default or website scope queues every store view it covers.
     * Only the ones that inherit the new connection end up connected; one that
     * keeps its own store or its own active flag does not, and is skipped.
     */
    public function testAWiderSaveProvisionsOnlyTheStoreViewsItConnected(): void
    {
        [$observer] = $this->observerForNewConnections(
            [1, 2, 3],
            [1, 3],
            [
                1 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new'],
                2 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new'],
                3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new'],
            ]
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertSame(
            [
                [1, 'mc-store-new', 'store1.example', true],
                [3, 'mc-store-new', 'store3.example', true],
            ],
            $this->provisioned
        );
    }

    /**
     * \Throwable, not \Exception: a TypeError in the provisioner must not turn
     * the save that connected the store into an error page either.
     */
    public function testANewConnectionFailureIsLoggedAndDoesNotBubble(): void
    {
        [$observer] = $this->observerForNewConnections(
            [3],
            [3],
            [3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new']],
            new \TypeError('mailchimp is down')
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertCount(1, $this->provisioned);
        $this->assertCount(1, $this->logged);
        $this->assertStringContainsString('could not provision the Pixel', $this->logged[0]);
        $this->assertStringContainsString('mailchimp is down', $this->logged[0]);
    }

    /**
     * One connection, one attempt: the queue is drained, so the next save of
     * the section does not provision the same store view again.
     */
    public function testTheQueueIsDrained(): void
    {
        [$observer, $queue] = $this->observerForNewConnections(
            [3],
            [3],
            [3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new']]
        );

        $observer->execute($this->createMock(EventObserver::class));
        $observer->execute($this->createMock(EventObserver::class));

        $this->assertCount(1, $this->provisioned);
        $this->assertSame([], $queue->drain());
    }

    /**
     * The toggle pass runs first, so a store view the new-connection pass
     * enables is not in front of it in the same request.
     */
    public function testNewConnectionsAreProvisionedAfterTheTogglePass(): void
    {
        [$observer] = $this->observerForNewConnections(
            [3],
            [1, 3],
            [
                1 => [
                    MailChimpHelper::XML_MAILCHIMP_STORE         => 'mc-store-1',
                    MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE => '1',
                ],
                3 => [MailChimpHelper::XML_MAILCHIMP_STORE => 'mc-store-new'],
            ]
        );

        $observer->execute($this->createMock(EventObserver::class));

        $this->assertSame(
            [
                [1, 'mc-store-1', 'store1.example', false],
                [3, 'mc-store-new', 'store3.example', true],
            ],
            $this->provisioned
        );
    }
}
