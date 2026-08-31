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
use Magento\Framework\Notification\NotifierInterface;
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
        $this->helper->expects($this->never())->method('saveConfigValueWithoutCacheFlush');

        $beacon->execute();
    }

    public function testUnauthorizedClearsTheToken(): void
    {
        $beacon = $this->makeBeacon('ebz_dead');

        $this->client->method('ping')->willReturn(new Response(Response::UNAUTHORIZED, 401));

        $this->helper->expects($this->once())
            ->method('saveConfigValueWithoutCacheFlush')
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
        $this->helper->expects($this->never())->method('saveConfigValueWithoutCacheFlush');

        $beacon->execute();
    }

    public function testServerErrorLeavesTheTokenAlone(): void
    {
        $beacon = $this->makeBeacon('ebz_live');

        $this->client->method('ping')->willReturn(new Response(Response::FAILED, 500));
        $this->helper->expects($this->never())->method('saveConfigValueWithoutCacheFlush');

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
            ->method('saveConfigValueWithoutCacheFlush')
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
        $this->helper->expects($this->never())->method('saveConfigValueWithoutCacheFlush');

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
        $this->helper->method('saveConfigValueWithoutCacheFlush')->willReturnCallback(
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

    /**
     * The ack queue must actually empty.
     *
     * Driven through a REAL NotificationDelivery, because the seam being tested
     * is the one between the two objects: ping() clears the pending uids and
     * then calls in, and delivery must not resurrect them. The helper double
     * models configuration the way Magento behaves within a single process —
     * writes land, but reads keep returning the value the scope was loaded
     * with. Mocking NotificationDelivery would skip the seam entirely, which is
     * how this went unnoticed.
     */
    public function testAcknowledgedUidsAreNotResurrectedByANewDelivery(): void
    {
        $stored = [
            'mailchimp/register/edge_token'        => 'ebz_tok',
            'mailchimp/register/last_delivery_uid' => 'uid-0,uid-1',
        ];
        // What a scope already loaded keeps returning, whatever is written after.
        $stale = $stored;
        $saved = [];

        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $store->method('getBaseUrl')->willReturn(self::STORE_URL);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn('key-123');
        $helper->method('getModuleVersion')->willReturn('103.4.81');
        $helper->method('getGmtDate')->willReturn('0');
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use (&$stale) {
                foreach ($stale as $known => $value) {
                    if (strpos($path, substr($known, strrpos($known, '/') + 1)) !== false) {
                        return $value;
                    }
                }
                return '';
            }
        );
        $registrar = function ($path, $value) use (&$saved) {
            $saved[$path] = $value;
        };
        // El beacon difiere sus escrituras; el NotificationDelivery real no.
        $helper->method('saveConfigValueWithoutCacheFlush')->willReturnCallback($registrar);
        $helper->method('saveConfigValue')->willReturnCallback($registrar);

        $client = $this->createMock(Client::class);
        $client->method('ping')->willReturn(
            new Response(Response::OK, 200, ['ok' => true, 'notifications' => 1])
        );
        $client->method('pullNotifications')->willReturn(
            new Response(
                Response::OK,
                200,
                ['notifications' => [[
                    'id'       => 'uid-2',
                    'subject'  => 'Scheduled maintenance',
                    'message'  => 'Maintenance window this weekend.',
                    'priority' => 'notice',
                ]]]
            )
        );

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        $beacon = new Beacon(
            $storeManager,
            $helper,
            $client,
            $signals,
            new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)),
            $metadata,
            $this->createMock(ScopeConfigInterface::class)
        );

        $beacon->execute();

        $this->assertSame(
            'uid-2',
            $saved['mailchimp/register/last_delivery_uid'],
            'uids already acknowledged were merged back in and would be sent again forever'
        );
    }

    /**
     * The cron slot is one install-wide value, so a first tick across N store
     * views must settle on one minute and write it once — not write N different
     * minutes, each read having missed the one before it.
     */
    public function testTheCronSlotIsClaimedOncePerProcessAcrossStoreViews(): void
    {
        $stores = [];
        foreach ([1, 2, 3] as $id) {
            $store = $this->createMock(Store::class);
            $store->method('getId')->willReturn($id);
            $store->method('getBaseUrl')->willReturn('https://view' . $id . '.example.com/');
            $stores[] = $store;
        }
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        $slotWrites = [];

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn('key-123');
        $helper->method('getModuleVersion')->willReturn('103.4.81');
        $helper->method('getGmtDate')->willReturn('0');
        $helper->method('getConfigValue')->willReturn('');
        $helper->method('saveConfigValueWithoutCacheFlush')->willReturnCallback(
            function ($path, $value) use (&$slotWrites) {
                if (strpos($path, 'beacon_cron') !== false) {
                    $slotWrites[] = $value;
                }
            }
        );

        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->method('info')->willReturn(['account_id' => 'acc-1', 'account_name' => 'Shop']);
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $api->root = $root;
        $helper->method('getApi')->willReturn($api);

        $client = $this->createMock(Client::class);
        $client->method('register')->willReturn(new Response(Response::OK, 201, ['token' => 'ebz_new']));
        $client->method('ping')->willReturn(new Response(Response::OK, 200, ['ok' => true, 'notifications' => 0]));

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        // Every read returns empty, which is what a stale in-process config
        // looks like on a first tick: without the guard each view would write.
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('');

        (new Beacon(
            $storeManager,
            $helper,
            $client,
            $signals,
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $scopeConfig
        ))->execute();

        $this->assertCount(1, $slotWrites, 'the cron slot was written once per store view instead of once');
        $this->assertMatchesRegularExpression('/^([0-9]|[1-5][0-9]) \* \* \* \*$/', $slotWrites[0]);
    }


    /**
     * Builds a beacon whose store answers the contact switch a given way, and
     * captures the body the register call would send.
     *
     * @param  mixed $shareContact value of the opt-out, null when unanswered
     * @param  array $captured     filled with the register body
     * @return Beacon
     */
    private function makeBeaconCapturingRegistration($shareContact, array &$captured)
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $store->method('getBaseUrl')->willReturn(self::STORE_URL);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn('key-123');
        $helper->method('getModuleVersion')->willReturn('103.4.81');
        $helper->method('getGmtDate')->willReturn('0');
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($shareContact) {
                if (strpos($path, 'share_contact') !== false) {
                    return $shareContact;
                }
                return '';
            }
        );

        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->method('info')->willReturn([
            'account_id'        => 'acc-1',
            'account_name'      => 'Shop',
            'email'             => 'owner@example.com',
            'first_name'        => 'Ada',
            'last_name'         => 'Lovelace',
            'username'          => 'ada',
            'pricing_plan_type' => 'monthly',
            'total_subscribers' => 4200,
        ]);
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $api->root = $root;
        $helper->method('getApi')->willReturn($api);

        $client = $this->createMock(Client::class);
        $client->method('register')->willReturnCallback(
            function ($body) use (&$captured) {
                $captured = $body;
                return new Response(Response::OK, 201, ['token' => 'ebz_new']);
            }
        );
        $client->method('ping')->willReturn(new Response(Response::OK, 200, ['ok' => true, 'notifications' => 0]));

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        return new Beacon(
            $storeManager,
            $helper,
            $client,
            $signals,
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $this->createMock(ScopeConfigInterface::class)
        );
    }

    /**
     * The admin switch says setting it to No leaves the account owner's name
     * and email address out of these reports. It has to be true.
     */
    public function testDecliningTheContactSwitchLeavesTheOwnerOutOfTheReport(): void
    {
        $body = [];
        $this->makeBeaconCapturingRegistration('0', $body)->execute();

        // account_name is here too: on an account opened by an individual it
        // commonly carries their name, and the API library withholds the same
        // value behind the same switch.
        foreach (['account_name', 'email', 'first_name', 'last_name', 'username'] as $field) {
            $this->assertArrayNotHasKey($field, $body, $field . ' was sent after the merchant opted out');
        }

        // What is not about a person keeps working, as promised — and the
        // account stays identifiable, so reports remain countable.
        $this->assertSame('acc-1', $body['account_id']);
        $this->assertSame('monthly', $body['pricing_plan_type']);
        $this->assertSame(4200, $body['total_subscribers']);
    }

    public function testAllowingTheContactSwitchSendsTheOwner(): void
    {
        $body = [];
        $this->makeBeaconCapturingRegistration('1', $body)->execute();

        $this->assertSame('Shop', $body['account_name']);
        $this->assertSame('owner@example.com', $body['email']);
        $this->assertSame('Ada', $body['first_name']);
        $this->assertSame('Lovelace', $body['last_name']);
        $this->assertSame('ada', $body['username']);
    }

    /**
     * An unanswered switch is not a refusal — that is what an upgrade from a
     * version predating it looks like. Same reading the API library gives the
     * very same config path.
     */
    public function testAnUnansweredContactSwitchIsNotTreatedAsARefusal(): void
    {
        $body = [];
        $this->makeBeaconCapturingRegistration(null, $body)->execute();

        $this->assertSame('owner@example.com', $body['email']);
    }


    /**
     * A run refreshes the config cache once, however many store views wrote.
     *
     * A flush is not cheap: the config module intercepts it to re-read the
     * whole system config and then serialise and encrypt every scope under a
     * lock, so its cost rises with the number of views. Flushing per write
     * would make a token expiry across N views pay that N times over, which is
     * quadratic work for one refresh.
     */
    public function testTheConfigCacheIsFlushedOncePerRunNotOncePerWrite(): void
    {
        $stores = [];
        foreach ([1, 2, 3, 4, 5] as $id) {
            $store = $this->createMock(Store::class);
            $store->method('getId')->willReturn($id);
            $store->method('getBaseUrl')->willReturn('https://view' . $id . '.example.com/');
            $stores[] = $store;
        }
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getApiKey')->willReturn('key-123');
        $helper->method('getModuleVersion')->willReturn('103.4.81');
        $helper->method('getGmtDate')->willReturn('0');
        // Every view holds a token, and every ping is refused as expired — the
        // wave this is about. Five views, five token clears, one refresh.
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) {
                return strpos($path, 'edge_token') !== false ? 'ebz_tok' : '';
            }
        );
        // Slot 0 falls on one of these five views, so ping() reaches
        // accountBlock() for it. Without this the helper hands back null and
        // the account read explodes — which the suite only survived because
        // Magento's unit bootstrap turns the "property on null" warning into a
        // PHPUnit exception that accountBlock()'s catch happens to swallow.
        // Depending on that is not a test, so the account path is stubbed.
        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->method('info')->willReturn(['account_id' => 'acc-1', 'account_name' => 'Shop']);
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $api->root = $root;
        $helper->method('getApi')->willReturn($api);

        $helper->expects($this->exactly(5))->method('saveConfigValueWithoutCacheFlush');
        $helper->expects($this->once())->method('flushConfigCache');

        $client = $this->createMock(Client::class);
        $client->method('ping')->willReturn(new Response(Response::UNAUTHORIZED, 401));

        $signals = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        (new Beacon(
            $storeManager,
            $helper,
            $client,
            $signals,
            $this->createMock(NotificationDelivery::class),
            $metadata,
            $this->createMock(ScopeConfigInterface::class)
        ))->execute();
    }

    /**
     * A run that writes nothing — the steady state, every hour of every day —
     * must not touch the cache at all.
     */
    public function testARunThatWritesNothingDoesNotFlush(): void
    {
        $beacon = $this->makeBeacon('ebz_tok');

        $this->client->method('ping')->willReturn(
            new Response(Response::OK, 200, ['ok' => true, 'notifications' => 0])
        );
        $this->helper->expects($this->never())->method('flushConfigCache');

        $beacon->execute();
    }


    /**
     * Builds a beacon over N store views that all hold a token.
     *
     * @param  array    $storeIds
     * @param  callable $enabled  answers isMailChimpEnabled, may throw
     * @param  array    $captured filled with helper interactions
     * @return array    [$beacon, $client]
     */
    private function makeBeaconOverStores(array $storeIds, $enabled, array &$captured)
    {
        $stores = [];
        foreach ($storeIds as $id) {
            $store = $this->createMock(Store::class);
            $store->method('getId')->willReturn($id);
            $store->method('getBaseUrl')->willReturn('https://view' . $id . '.example.com/');
            $stores[] = $store;
        }
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturnCallback($enabled);
        $helper->method('getApiKey')->willReturn('key-123');
        $helper->method('getModuleVersion')->willReturn('103.4.81');
        $helper->method('getGmtDate')->willReturn('0');
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) {
                return strpos($path, 'edge_token') !== false ? 'ebz_tok' : '';
            }
        );
        $helper->method('saveConfigValueWithoutCacheFlush')->willReturnCallback(
            function () use (&$captured) {
                $captured['writes'] = ($captured['writes'] ?? 0) + 1;
            }
        );
        $helper->method('flushConfigCache')->willReturnCallback(
            function () use (&$captured) {
                $captured['flushes'] = ($captured['flushes'] ?? 0) + 1;
            }
        );

        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()->onlyMethods(['info'])->getMock();
        $root->method('info')->willReturn(['account_id' => 'acc-1', 'account_name' => 'Shop']);
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $api->root = $root;
        $helper->method('getApi')->willReturn($api);

        $client   = $this->createMock(Client::class);
        $signals  = $this->createMock(LivenessSignals::class);
        $signals->method('forStore')->willReturn(['last_error_type' => null]);
        $metadata = $this->createMock(ProductMetadataInterface::class);
        $metadata->method('getVersion')->willReturn('2.4.8');
        $metadata->method('getEdition')->willReturn('Community');

        return [
            new Beacon(
                $storeManager,
                $helper,
                $client,
                $signals,
                $this->createMock(NotificationDelivery::class),
                $metadata,
                $this->createMock(ScopeConfigInterface::class)
            ),
            $client,
        ];
    }

    /**
     * An Error is not an Exception, so catching only Exception let one store
     * view's broken API path abort the run and take every remaining view with
     * it. One view's problem is not the others'.
     */
    public function testAnErrorInOneStoreViewDoesNotAbortTheRest(): void
    {
        $captured = [];
        list($beacon, $client) = $this->makeBeaconOverStores(
            [1, 2, 3],
            function () {
                return true;
            },
            $captured
        );

        $pinged = [];
        $client->method('ping')->willReturnCallback(
            function ($token, $body) use (&$pinged) {
                $pinged[] = $body['store_url'];
                if (count($pinged) === 2) {
                    throw new \TypeError('the API path is broken for this view');
                }
                return new Response(Response::UNAUTHORIZED, 401);
            }
        );

        $beacon->execute();

        $this->assertCount(3, $pinged, 'the run stopped at the store view that raised an Error');
    }

    /**
     * The deferred writes are already in the database, so a run that ends
     * without flushing leaves a cleared token stored while the config cache
     * keeps serving the old one. Whatever happens, the refresh has to occur.
     */
    public function testTheFlushStillHappensWhenTheRunIsCutShort(): void
    {
        $captured = [];
        list($beacon, $client) = $this->makeBeaconOverStores(
            [1, 2, 3],
            function ($storeId) {
                // Outside the per-store guard, so this ends the loop.
                if ($storeId === 3) {
                    throw new \Error('the store manager handed back something broken');
                }
                return true;
            },
            $captured
        );
        $client->method('ping')->willReturn(new Response(Response::UNAUTHORIZED, 401));

        try {
            $beacon->execute();
        } catch (\Throwable $t) {
            // Escaping is acceptable; losing the flush is not.
        }

        $this->assertSame(2, $captured['writes'] ?? 0, 'the two healthy views should have written');
        $this->assertSame(1, $captured['flushes'] ?? 0, 'the config cache was left stale after an aborted run');
    }

}
