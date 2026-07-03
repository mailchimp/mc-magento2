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

        return new ConfigObserver($storeManager, $registry, $helper, $provisioner, $reinitableConfig);
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

        $observer = new ConfigObserver($storeManager, $registry, $helper, $provisioner, $reinitableConfig);

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
}
