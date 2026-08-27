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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Edge\Beacon;
use Ebizmarts\MailChimp\Model\Edge\Client;
use Ebizmarts\MailChimp\Model\Edge\LivenessSignals;
use Ebizmarts\MailChimp\Model\Edge\NotificationDelivery;
use Ebizmarts\MailChimp\Model\Edge\Response;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class BeaconTest extends TestCase
{
    const STORE_ID  = 1;
    const STORE_URL = 'https://shop.example.com/';

    /**
     * @var MailChimpHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helper;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $client;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * Builds a beacon over one active store view.
     *
     * @param  string $token        stored edge token, '' when unregistered
     * @param  bool   $accountFails whether GET / against Mailchimp blows up
     * @param  int    $hour         GMT hour the tick runs at
     * @return Beacon
     */
    private function makeBeacon($token, $accountFails = false, $hour = 0, $pendingAcks = '')
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $store->method('getBaseUrl')->willReturn(self::STORE_URL);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $this->helper = $this->createMock(MailChimpHelper::class);
        $this->helper->method('isMailChimpEnabled')->willReturn(true);
        $this->helper->method('getApiKey')->willReturn('key-123');
        $this->helper->method('getModuleVersion')->willReturn('103.4.81');
        $this->helper->method('getGmtDate')->willReturn((string)$hour);
        $this->helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($token, $pendingAcks) {
                if (strpos($path, 'edge_token') !== false) {
                    return $token;
                }
                if (strpos($path, 'last_delivery_uid') !== false) {
                    return $pendingAcks;
                }
                return '';
            }
        );

        if ($accountFails) {
            $this->helper->method('getApi')->willThrowException(new \Exception('Mailchimp is down'));
        } else {
            $root = $this->getMockBuilder(\Mailchimp_Root::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['info'])
                ->getMock();
            $root->method('info')->willReturn(['account_id' => 'acc-1', 'account_name' => 'Shop']);

            $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
            $api->root = $root;

            $this->helper->method('getApi')->willReturn($api);
        }

        $this->client      = $this->createMock(Client::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);

        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        return new Beacon(
            $storeManager,
            $this->helper,
            $this->client,
            $signals,
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $this->scopeConfig
        );
    }

    public function testUnregisteredStoreRegistersThenPings(): void
    {
        $beacon = $this->makeBeacon('');

        $this->client->expects($this->once())
            ->method('register')
            ->willReturn(new Response(Response::OK, 201, ['token' => 'ebz_new']));
        $this->client->expects($this->once())
            ->method('ping')
            ->with('ebz_new', $this->anything())
            ->willReturn(new Response(Response::OK, 200, ['ok' => true, 'notifications' => 0]));

        $beacon->execute();
    }

    public function testAFailedRegisterWritesNothing(): void
    {
        $beacon = $this->makeBeacon('');

        $this->client->method('register')->willReturn(new Response(Response::FAILED, 503));
        $this->client->expects($this->never())->method('ping');
        $this->helper->expects($this->never())->method('saveConfigValue');

        $beacon->execute();
    }

    public function testUnauthorizedClearsTheToken(): void
    {
        $beacon = $this->makeBeacon('ebz_dead');

        $this->client->method('ping')->willReturn(new Response(Response::UNAUTHORIZED, 401));

        $this->helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->stringContains('edge_token'), '', self::STORE_ID, $this->anything());

        $beacon->execute();
    }

    /**
     * If a 429 cleared the token, one throttled hour would make every installation
     * re-register all at once.
     */
    public function testRateLimitedLeavesTheTokenAlone(): void
    {
        $beacon = $this->makeBeacon('ebz_live');

        $this->client->method('ping')->willReturn(new Response(Response::RATE_LIMITED, 429, [], 60));
        $this->helper->expects($this->never())->method('saveConfigValue');

        $beacon->execute();
    }

    public function testServerErrorLeavesTheTokenAlone(): void
    {
        $beacon = $this->makeBeacon('ebz_live');

        $this->client->method('ping')->willReturn(new Response(Response::FAILED, 500));
        $this->helper->expects($this->never())->method('saveConfigValue');

        $beacon->execute();
    }

    /**
     * The status report is the point and the account data rides along: a Mailchimp
     * outage must not make every installation look dead at once.
     */
    public function testAFailingAccountCallDoesNotSuppressThePing(): void
    {
        // Hour chosen so this tick IS one of the store's two account slots.
        $slot   = (crc32(self::STORE_ID . self::STORE_URL) & 0x7fffffff) % Beacon::ACCOUNT_BLOCK_PERIOD_HOURS;
        $beacon = $this->makeBeacon('ebz_live', true, $slot);

        $this->client->expects($this->once())
            ->method('ping')
            ->with(
                'ebz_live',
                $this->callback(function ($body) {
                    return !array_key_exists('account_id', $body)
                        && !array_key_exists('account', $body);
                })
            )
            ->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    public function testAccountBlockRidesTheStoresOwnSlot(): void
    {
        $slot   = (crc32(self::STORE_ID . self::STORE_URL) & 0x7fffffff) % Beacon::ACCOUNT_BLOCK_PERIOD_HOURS;
        $beacon = $this->makeBeacon('ebz_live', false, $slot);

        $this->client->expects($this->once())
            ->method('ping')
            ->with(
                'ebz_live',
                $this->callback(function ($body) {
                    return ($body['account_id'] ?? null) === 'acc-1'
                        && ($body['account_name'] ?? null) === 'Shop'
                        && !array_key_exists('account', $body);
                })
            )
            ->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    public function testNoAccountBlockOutsideTheSlot(): void
    {
        $slot   = (crc32(self::STORE_ID . self::STORE_URL) & 0x7fffffff) % Beacon::ACCOUNT_BLOCK_PERIOD_HOURS;
        $beacon = $this->makeBeacon('ebz_live', false, ($slot + 1) % Beacon::ACCOUNT_BLOCK_PERIOD_HOURS);

        $this->client->expects($this->once())
            ->method('ping')
            ->with(
                'ebz_live',
                $this->callback(function ($body) {
                    return !array_key_exists('account_id', $body)
                        && !array_key_exists('account', $body);
                })
            )
            ->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    public function testStoreWithoutApiKeyIsSkipped(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $store->method('getBaseUrl')->willReturn(self::STORE_URL);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn('');

        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('register');
        $client->expects($this->never())->method('ping');

        $metadata = $this->createMock(ProductMetadataInterface::class);

        (new Beacon(
            $storeManager,
            $helper,
            $client,
            $this->createMock(LivenessSignals::class),
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $this->createMock(ScopeConfigInterface::class)
        ))->execute();
    }

    public function testQueuedAcksRideTheNextPing(): void
    {
        $beacon = $this->makeBeacon('ebz_live', false, 0, 'uid-1,uid-2');

        $this->client->expects($this->once())
            ->method('ping')
            ->with(
                'ebz_live',
                $this->callback(function ($body) {
                    return isset($body['received'])
                        && $body['received'] === ['uid-1', 'uid-2']
                        && !array_key_exists('read', $body);
                })
            )
            ->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    public function testAcksAreClearedOnceTheEdgeHasSeenThem(): void
    {
        $beacon = $this->makeBeacon('ebz_live', false, 0, 'uid-1');

        $this->client->method('ping')->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $this->helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->stringContains('last_delivery_uid'), '', self::STORE_ID, $this->anything());

        $beacon->execute();
    }

    /**
     * A ping that never landed has confirmed nothing, so the queue must survive.
     */
    public function testAFailedPingKeepsTheAcksQueued(): void
    {
        $beacon = $this->makeBeacon('ebz_live', false, 0, 'uid-1');

        $this->client->method('ping')->willReturn(new Response(Response::FAILED, 500));
        $this->helper->expects($this->never())->method('saveConfigValue');

        $beacon->execute();
    }

    /**
     * Regression guard for a silent data loss: the service projects the beacon body
     * onto a CLOSED whitelist and drops anything outside it. Sending
     * `extension_version` instead of `module_version` cost nothing visible — the
     * ping still returned 200 — while the service's module_version column was never
     * written for this store.
     */
    public function testVersionsUseTheKeyNamesTheEdgeAccepts(): void
    {
        $beacon = $this->makeBeacon('ebz_live');

        $this->client->expects($this->once())
            ->method('ping')
            ->with(
                'ebz_live',
                $this->callback(function ($body) {
                    return ($body['module_version'] ?? null) === '103.4.81'
                        && ($body['magento_version'] ?? null) === '2.4.8'
                        && isset($body['php_version'])
                        && !array_key_exists('extension_version', $body)
                        && !array_key_exists('magento_edition', $body);
                })
            )
            ->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    public function testRegisterCarriesTheSameVersionKeys(): void
    {
        $beacon = $this->makeBeacon('');

        $this->client->expects($this->once())
            ->method('register')
            ->with($this->callback(function ($body) {
                return ($body['module_version'] ?? null) === '103.4.81'
                    && !array_key_exists('extension_version', $body);
            }))
            ->willReturn(new Response(Response::OK, 201, ['token' => 'ebz_new']));
        $this->client->method('ping')->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $beacon->execute();
    }

    /**
     * crc32() is negative on 32-bit PHP builds. Unmasked, the derived minute
     * would be negative and the written cron expression invalid, so the job
     * would never be scheduled and the install would never report — silently.
     */
    public function testTheClaimedCronExpressionIsAlwaysValid(): void
    {
        $beacon = $this->makeBeacon('');

        $this->client->method('register')->willReturn(new Response(Response::OK, 201, ['token' => 'ebz_new']));
        $this->client->method('ping')->willReturn(new Response(Response::OK, 200, ['notifications' => 0]));

        $written = null;
        $this->helper->method('saveConfigValue')->willReturnCallback(
            function ($path, $value) use (&$written) {
                if (strpos($path, 'beacon_cron') !== false) {
                    $written = $value;
                }
            }
        );

        $beacon->execute();

        $this->assertNotNull($written, 'the first successful register must claim a slot');
        $this->assertMatchesRegularExpression('/^([0-9]|[1-5][0-9]) \* \* \* \*$/', $written);
    }
}
