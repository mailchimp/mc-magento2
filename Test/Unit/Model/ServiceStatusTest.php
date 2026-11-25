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

namespace Ebizmarts\MailChimp\Test\Unit\Model;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ServiceStatus;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Reading a service status the library verified and stored, against the
 * library's real reader, signed with a key pair generated for the run.
 */
class ServiceStatusTest extends TestCase
{
    /** @var string */
    private $dir;

    /** @var array */
    private $keys;

    /** @var string */
    private $secret;

    /** @var string */
    private $readerClass;

    protected function setUp(): void
    {
        // Red, not skipped: a library in vendor without the reader under either
        // name the module looks for means the notice and the cron skip are
        // silently gone, which is exactly what these tests are for.
        $class = (new ServiceStatus($this->createMock(MailChimpHelper::class), $this->createMock(StoreManagerInterface::class)))
            ->readerClass();
        $this->assertNotNull($class, 'mailchimp-lib in vendor has no Mailchimp_ServiceStatus (nor Mailchimp_Hold)');
        $this->readerClass = $class;
        if (!$class::supported()) {
            $this->markTestSkipped('no sodium on this PHP: the library ignores every service status by design');
        }
        $pair         = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->keys   = ['test' => self::b64url(sodium_crypto_sign_publickey($pair))];
        $this->dir    = sys_get_temp_dir() . '/mc-svcstatus-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0700);
    }

    protected function tearDown(): void
    {
        if ($this->dir && is_dir($this->dir)) {
            array_map('unlink', glob($this->dir . '/*'));
            rmdir($this->dir);
        }
    }

    private static function b64url($bytes)
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function pause($apiKey, array $overrides = [])
    {
        $claim = array_merge([
            'v' => 1, 'kind' => 'sync_hold', 'install_id' => \Mailchimp_Telemetry::installId($apiKey),
            'code' => 'DEPRECATED', 'reason' => 'product_retired', 'ref' => 'lh3',
            'iat' => time() - 60, 'exp' => time() + 3600, 'nonce' => self::b64url(random_bytes(16)),
        ], $overrides);
        $bytes = json_encode($claim);
        $class  = $this->readerClass;
        $reader = new $class($this->keys, $this->dir);
        $this->assertTrue($reader->apply([
            'claim' => self::b64url($bytes),
            'sig'   => self::b64url(sodium_crypto_sign_detached($bytes, $this->secret)),
            'kid'   => 'test',
        ], \Mailchimp_Telemetry::installId($apiKey)));
    }

    /**
     * @param array $stores store id => [api key, name, enabled]
     */
    private function makeServiceStatus(array $stores, $readerClasses = null)
    {
        $storeObjects = [];
        foreach ($stores as $id => $row) {
            $store = $this->createMock(Store::class);
            $store->method('getId')->willReturn($id);
            $store->method('getName')->willReturn($row[1]);
            $storeObjects[] = $store;
        }
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($storeObjects);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getApiKey')->willReturnCallback(function ($storeId) use ($stores) {
            return $stores[$storeId][0] ?? '';
        });
        $helper->method('isMailChimpEnabled')->willReturnCallback(function ($storeId) use ($stores) {
            return $stores[$storeId][2] ?? false;
        });

        $keys = $this->keys;
        $dir  = $this->dir;

        return new class($helper, $storeManager, $keys, $dir, $readerClasses) extends ServiceStatus {
            private $k;
            private $d;
            private $c;
            public function __construct($helper, $storeManager, $k, $d, $c)
            {
                parent::__construct($helper, $storeManager);
                $this->k = $k;
                $this->d = $d;
                $this->c = $c;
            }
            protected function readerClasses()
            {
                return $this->c !== null ? $this->c : parent::readerClasses();
            }
            protected function createReader($class)
            {
                return new $class($this->k, $this->d);
            }
        };
    }

    public function testNoStatusNothingPaused(): void
    {
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);

        $this->assertNull($statuses->forStore(1));
        $this->assertFalse($statuses->isPaused(1));
        $this->assertSame([], $statuses->active());
    }

    public function testAPauseOnTheStoresKeyPausesIt(): void
    {
        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);

        $this->assertSame('DEPRECATED', $statuses->forStore(1)['code']);
        $this->assertTrue($statuses->isPaused(1));
    }

    public function testAPauseOnAnotherKeyDoesNot(): void
    {
        $this->pause('key-b-us1');
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);

        $this->assertFalse($statuses->isPaused(1));
    }

    public function testAFutureStartWarnsWithoutPausing(): void
    {
        $this->pause('key-a-us1', ['blocks_from' => time() + 600]);
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);

        $this->assertNotNull($statuses->forStore(1));
        $this->assertFalse($statuses->isPaused(1));
    }

    public function testAStoreWithoutAKeyIsNeverPaused(): void
    {
        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([1 => ['', 'Main', true]]);

        $this->assertFalse($statuses->isPaused(1));
    }

    public function testActiveGroupsStoreViewsByKeyAndSkipsDisabledOnes(): void
    {
        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([
            1 => ['key-a-us1', 'English', true],
            2 => ['key-a-us1', 'French', true],
            3 => ['key-a-us1', 'Disabled', false],
            4 => ['key-c-us1', 'Other account', true],
        ]);

        $active = $statuses->active();
        $this->assertCount(1, $active);
        $this->assertSame(['English', 'French'], $active[0]['stores']);
        $this->assertSame('lh3', $active[0]['status']['ref']);
    }

    public function testTheReportSaysWhatTheLibraryHas(): void
    {
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);
        $this->assertSame(
            ['svc_state' => 'none', 'svc_ref' => null, 'svc_code' => null, 'svc_since' => null],
            $statuses->report(1)
        );

        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);
        $report   = $statuses->report(1);
        $this->assertSame('held', $report['svc_state']);
        $this->assertSame('lh3', $report['svc_ref']);
        $this->assertSame('DEPRECATED', $report['svc_code']);
        $this->assertIsInt($report['svc_since']);
    }

    public function testAFutureStartIsReportedAsAWarning(): void
    {
        $this->pause('key-a-us1', ['blocks_from' => time() + 600]);
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);

        $this->assertSame('warning', $statuses->report(1)['svc_state']);
    }

    public function testAStoreWithoutAKeyReportsNothing(): void
    {
        $this->assertSame([], $this->makeServiceStatus([1 => ['', 'Main', true]])->report(1));
    }

    public function testResetReadsAgain(): void
    {
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]]);
        $this->assertFalse($statuses->isPaused(1));

        $this->pause('key-a-us1');
        $this->assertFalse($statuses->isPaused(1), 'cached for the process until reset');

        $statuses->reset();
        $this->assertTrue($statuses->isPaused(1));
    }

    public function testNoReaderUnderEitherNameNothingIsPausedOrReported(): void
    {
        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]], ['Mailchimp_NoSuchReader']);

        $this->assertNull($statuses->readerClass());
        $this->assertFalse($statuses->isPaused(1));
        $this->assertSame([], $statuses->report(1));
        $this->assertSame([], $statuses->active());
    }

    public function testTheNameBefore3054IsAcceptedToo(): void
    {
        if (!class_exists('Mailchimp_Hold')) {
            // The library as it was named before the release: same reader.
            class_alias($this->readerClass, 'Mailchimp_Hold');
        }
        $this->pause('key-a-us1');
        $statuses = $this->makeServiceStatus([1 => ['key-a-us1', 'Main', true]], ['Mailchimp_Hold']);

        $this->assertSame('Mailchimp_Hold', $statuses->readerClass());
        $this->assertTrue($statuses->isPaused(1));
    }
}
