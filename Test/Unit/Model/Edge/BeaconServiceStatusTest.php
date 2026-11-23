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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Edge\Beacon;
use Ebizmarts\MailChimp\Model\Edge\Client;
use Ebizmarts\MailChimp\Model\Edge\ConfigSnapshot;
use Ebizmarts\MailChimp\Model\Edge\LivenessSignals;
use Ebizmarts\MailChimp\Model\Edge\NotificationDelivery;
use Ebizmarts\MailChimp\Model\Edge\Response;
use Ebizmarts\MailChimp\Model\ServiceStatus;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * The ping's part in the QoS service status: it carries the library's
 * installation id and the service status report, and hands any control in the
 * reply to the library.
 */
class BeaconServiceStatusTest extends TestCase
{
    const API_KEY = 'abc123-us7';

    /** @var Client|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var \Mailchimp|\PHPUnit\Framework\MockObject\MockObject */
    private $api;

    /** @var array the body of the last ping */
    private $pinged = null;

    protected function setUp(): void
    {
        // Red, not skipped: see Test\Unit\Model\ServiceStatusTest.
        $this->assertTrue(
            method_exists(\Mailchimp::class, 'applyControl'),
            'mailchimp-lib in vendor has no Mailchimp::applyControl()'
        );
    }

    private function makeBeacon($token, $paused, $hour = 0, array $report = [])
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getBaseUrl')->willReturn('https://shop.example.com/');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn(self::API_KEY);
        $helper->method('getModuleVersion')->willReturn('103.4.88');
        $helper->method('getGmtDate')->willReturn((string)$hour);
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($token) {
                return strpos($path, 'edge_token') !== false ? $token : '';
            }
        );

        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->method('info')->willReturn(['account_id' => 'acc-1']);

        $this->api = $this->getMockBuilder(\Mailchimp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyControl'])
            ->getMock();
        $this->api->root = $root;
        $helper->method('getApi')->willReturn($this->api);

        $this->client = $this->createMock(Client::class);
        $this->client->method('ping')->willReturnCallback(
            function ($token, array $body) {
                $this->pinged = $body;
                return $this->pingReply;
            }
        );

        $this->serviceStatus = $this->createMock(ServiceStatus::class);
        $this->serviceStatus->method('isPaused')->willReturn($paused);
        $this->serviceStatus->method('report')->willReturn($report);

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn([]);
        $config = $this->createMock(ConfigSnapshot::class);
        $config->method('forStore')->willReturn([]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');

        return new Beacon(
            $storeManager,
            $helper,
            $this->client,
            $signals,
            $config,
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $this->createMock(ScopeConfigInterface::class),
            $this->serviceStatus
        );
    }

    /** @var Response */
    private $pingReply;

    /** @var ServiceStatus|\PHPUnit\Framework\MockObject\MockObject */
    private $serviceStatus;

    public function testThePingCarriesTheLibraryInstallationId(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        $this->makeBeacon('ebz_token', false)->execute();

        $this->assertSame(\Mailchimp_Telemetry::installId(self::API_KEY), $this->pinged['install_id']);
        $this->assertSame('us7', $this->pinged['dc']);
        $this->assertSame(\Mailchimp_Telemetry::libVersion(), $this->pinged['lib_version'] ?? '');
        $this->assertStringNotContainsString(self::API_KEY, json_encode($this->pinged));
    }

    public function testAControlInTheReplyGoesToTheLibrary(): void
    {
        $control = ['claim' => 'c', 'sig' => 's', 'kid' => 'k'];
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true, 'control' => $control]);
        $beacon = $this->makeBeacon('ebz_token', false);

        $this->api->expects($this->once())->method('applyControl')->with($control);

        $beacon->execute();
    }

    public function testNoControlNothingApplied(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        $beacon = $this->makeBeacon('ebz_token', false);

        $this->api->expects($this->never())->method('applyControl');

        $beacon->execute();
    }

    public function testAFailedPingAppliesNothing(): void
    {
        $this->pingReply = new Response(Response::FAILED, 500, ['control' => ['claim' => 'c']]);
        $beacon = $this->makeBeacon('ebz_token', false);

        $this->api->expects($this->never())->method('applyControl');

        $beacon->execute();
    }

    public function testAPausedStoreDoesNotTryToRegister(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        $beacon = $this->makeBeacon('', true);

        $this->client->expects($this->never())->method('register');

        $beacon->execute();
        $this->assertNull($this->pinged);
    }

    public function testAPausedStoreStillPingsWithoutTheAccountBlock(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        // Every hour is a slot for some store; try them all so one is ours.
        for ($hour = 0; $hour < 12; $hour++) {
            $this->pinged = null;
            $this->makeBeacon('ebz_token', true, $hour)->execute();
            $this->assertIsArray($this->pinged);
            $this->assertArrayNotHasKey('account_id', $this->pinged, "hour $hour");
            $this->assertArrayHasKey('install_id', $this->pinged);
        }
    }

    public function testThePingCarriesTheServiceStatusReport(): void
    {
        $report = ['svc_state' => 'held', 'svc_ref' => 'lh4', 'svc_code' => 'BLOCK', 'svc_since' => 1800000000];
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        $this->makeBeacon('ebz_token', true, 0, $report)->execute();

        foreach ($report as $field => $value) {
            $this->assertSame($value, $this->pinged[$field], $field);
        }
    }

    public function testNoReportNoFields(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true]);
        $this->makeBeacon('ebz_token', false)->execute();

        $this->assertArrayNotHasKey('svc_state', $this->pinged);
    }

    public function testAnAppliedControlIsReadAgain(): void
    {
        $this->pingReply = new Response(Response::OK, 200, ['ok' => true, 'control' => ['claim' => 'c']]);
        $beacon = $this->makeBeacon('ebz_token', false);

        $this->serviceStatus->expects($this->once())->method('reset');

        $beacon->execute();
    }
}
