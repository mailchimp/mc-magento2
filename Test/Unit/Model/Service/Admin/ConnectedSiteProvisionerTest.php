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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Service\Admin;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner;
use Ebizmarts\MailChimp\Model\Service\Pixel\PixelStateWriter;
use PHPUnit\Framework\TestCase;

/**
 * What the provisioner saves when Mailchimp refuses to turn the Pixel on.
 *
 * The connection hook (bound site only) must save nothing then. Saving the
 * script sets the toggle to Yes for a Pixel Mailchimp never enabled, and the
 * toggle cannot fix it: a later Yes provisions only a store view with no script
 * saved. With nothing saved the toggle stays No, and the merchant's own Yes
 * calls enable-pixel again -- which is what "log-only, the merchant can turn it
 * on by hand" relies on.
 *
 * A merchant's own Yes keeps the behaviour it has had since the Pixel shipped.
 *
 * Every test runs against a Mailchimp store with a bound connected site that
 * has a script URL and a fragment.
 */
class ConnectedSiteProvisionerTest extends TestCase
{
    private const STORE_ID  = 3;
    private const MC_STORE  = 'mc-store-id';
    private const SITE      = 'site-foreign-id';
    private const URL       = 'https://chimpstatic.com/mcjs-connected/js/users/x/y.js';
    private const FRAGMENT  = '<script id="mcjs">x</script>';

    /** @var string[] */
    private $logged = [];

    /** @var string[] */
    private $calls = [];

    /**
     * @param  \Throwable|null $enablePixelError what enable-pixel throws, if anything
     * @param  PixelStateWriter $writer
     * @return ConnectedSiteProvisioner
     */
    private function provisioner(?\Throwable $enablePixelError, PixelStateWriter $writer): ConnectedSiteProvisioner
    {
        $this->logged = [];
        $this->calls  = [];

        $api = new class ($enablePixelError, $this->calls) {
            public $ecommerce;
            private $error;
            private $calls;

            public function __construct($error, array &$calls)
            {
                $this->error = $error;
                $this->calls = &$calls;
                $this->ecommerce = new \stdClass();
                $this->ecommerce->stores = new class {
                    public function get($id)
                    {
                        return ['connected_site' => [
                            'site_foreign_id' => 'site-foreign-id',
                            'site_script'     => [
                                'url'      => 'https://chimpstatic.com/mcjs-connected/js/users/x/y.js',
                                'fragment' => '<script id="mcjs">x</script>',
                            ],
                        ]];
                    }
                };
            }

            public function call($path, $params, $method)
            {
                $this->calls[] = $method . ' ' . $path;
                if ($this->error && substr($path, -strlen('/actions/enable-pixel')) === '/actions/enable-pixel') {
                    throw $this->error;
                }
                return [];
            }
        };

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getApi', 'log'])
            ->getMock();
        $helper->method('getApi')->willReturn($api);
        $helper->method('log')->willReturnCallback(function ($message) {
            $this->logged[] = $message;
        });

        return new ConnectedSiteProvisioner($helper, $writer);
    }

    /**
     * @return array
     */
    public static function enablePixelFailureProvider(): array
    {
        return [
            'Mailchimp answers with an error' => [
                new \Mailchimp_Error('/connected-sites/site-foreign-id/actions/enable-pixel', 'POST', '', 'Bad Request', 'nope'),
            ],
            'Mailchimp cannot be reached: DNS, TLS or a timeout' => [
                new \Mailchimp_HttpError('/connected-sites/site-foreign-id/actions/enable-pixel', 'POST', '', '', 'Operation timed out'),
            ],
        ];
    }

    /**
     * @dataProvider enablePixelFailureProvider
     */
    public function testAFailedEnablePixelOnConnectionSavesNothing(\Throwable $error): void
    {
        $writer = $this->createMock(PixelStateWriter::class);
        $writer->expects($this->never())->method('enable');

        $this->provisioner($error, $writer)->provision(self::STORE_ID, self::MC_STORE, 'example.com', true);
    }

    /**
     * The log is the only trace of it now, so it says which store view and
     * how to get the Pixel on.
     */
    public function testAFailedEnablePixelOnConnectionIsLoggedForThatStoreView(): void
    {
        $error = new \Mailchimp_Error('/', 'POST', '', 'Bad Request', 'nope');

        $this->provisioner($error, $this->createMock(PixelStateWriter::class))
            ->provision(self::STORE_ID, self::MC_STORE, 'example.com', true);

        $this->assertStringContainsString('enable-pixel failed', $this->logged[0]);
        $this->assertStringContainsString('store ' . self::STORE_ID, $this->logged[1]);
        $this->assertStringContainsString('Turning it on in the admin will try again', $this->logged[1]);
    }

    public function testASuccessfulEnablePixelOnConnectionSavesTheScript(): void
    {
        $writer = $this->createMock(PixelStateWriter::class);
        $writer->expects($this->once())->method('enable')->with(self::STORE_ID, self::URL, self::FRAGMENT);

        $this->provisioner(null, $writer)->provision(self::STORE_ID, self::MC_STORE, 'example.com', true);

        $this->assertSame(['POST /connected-sites/' . self::SITE . '/actions/enable-pixel'], $this->calls);
    }

    /**
     * Deliberately unchanged: a merchant's own Yes still saves the script when
     * enable-pixel fails, as it has since the Pixel shipped. Changing that
     * path is outside 103.4.87.
     */
    public function testAMerchantsOwnYesStillSavesTheScriptWhenEnablePixelFails(): void
    {
        $error = new \Mailchimp_Error('/', 'POST', '', 'Bad Request', 'nope');

        $writer = $this->createMock(PixelStateWriter::class);
        $writer->expects($this->once())->method('enable')->with(self::STORE_ID, self::URL, self::FRAGMENT);

        $this->provisioner($error, $writer)->provision(self::STORE_ID, self::MC_STORE, 'example.com', false);
    }
}
