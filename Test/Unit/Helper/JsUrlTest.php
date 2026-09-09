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

namespace Ebizmarts\MailChimp\Test\Unit\Helper;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use PHPUnit\Framework\TestCase;

/**
 * getJsUrl() runs during page render, so every call it makes is paid by a
 * visitor. These pin the two ways it used to keep calling forever.
 */
class JsUrlTest extends TestCase
{
    /** @var int */
    private $apiCalls = 0;

    /** @var array */
    private $saved = [];

    /**
     * @param  mixed $storeId   value of mailchimp/general/monkeystore
     * @param  mixed $savedUrl  value already in config, or ''
     * @param  mixed $answer    API answer, or a throwable
     * @return MailChimpHelper
     */
    private function helper($storeId, $savedUrl, $answer = null)
    {
        $this->apiCalls = 0;
        $this->saved    = [];

        $api = new \stdClass();
        $api->ecommerce = new \stdClass();
        $api->ecommerce->stores = new class($answer, $this) {
            private $answer;
            private $test;
            public function __construct($answer, $test) { $this->answer = $answer; $this->test = $test; }
            public function get($id)
            {
                $this->test->countApiCall();
                if ($this->answer instanceof \Throwable) { throw $this->answer; }
                return $this->answer;
            }
        };

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigValue', 'getApi', 'saveConfigValue', 'log'])
            ->getMock();

        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($savedUrl, $storeId) {
                if ($path === MailChimpHelper::XML_MAILCHIMP_JS_URL) { return $savedUrl; }
                if ($path === MailChimpHelper::XML_PATH_ACTIVE)      { return 1; }
                if ($path === MailChimpHelper::XML_MAILCHIMP_STORE)  { return $storeId; }
                return null;
            }
        );
        $helper->method('getApi')->willReturn($api);
        $helper->method('saveConfigValue')->willReturnCallback(function ($path, $value) {
            $this->saved[$path] = $value;
        });

        return $helper;
    }

    public function countApiCall()
    {
        $this->apiCalls++;
    }

    /**
     * Bug 1: the admin placeholder went straight to the API as store "-1",
     * which is a guaranteed 404, once per uncached render, forever.
     */
    public function testThePlaceholderNeverReachesTheApi()
    {
        $helper = $this->helper(-1, '');

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame(0, $this->apiCalls);
    }

    public function testThePlaceholderAsAStringNeverReachesTheApi()
    {
        $helper = $this->helper('-1', '');

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame(0, $this->apiCalls);
    }

    /**
     * MonkeyStore offers '---No Data---' with value 0 whenever there is no API
     * key at that scope or the store listing threw, so 0 is reachable from the
     * admin. It is the quieter failure of the two: the library branches on
     * if($id), so a 0 lists the stores instead of asking for one, gets a 200
     * with no connected_site, saves nothing, and repeats with no error at all.
     */
    public function testTheNoDataOptionNeverReachesTheApi()
    {
        foreach ([0, '0'] as $noData) {
            $helper = $this->helper($noData, '');
            $this->assertSame('', $helper->getJsUrl(1));
            $this->assertSame(0, $this->apiCalls);
        }
    }

    public function testAnUnsetStoreNeverReachesTheApi()
    {
        foreach (['', null] as $unset) {
            $helper = $this->helper($unset, '');
            $helper->getJsUrl(1);
            $this->assertSame(0, $this->apiCalls);
        }
    }

    /**
     * Bug 2: the success path wrote with the raw resource model and left the
     * config cache serving the old empty value, so the next render called
     * again -- a lookup that succeeded and never stopped asking.
     */
    public function testASuccessfulLookupIsSavedThroughTheCacheFlushingHelper()
    {
        $helper = $this->helper(
            'abc123',
            '',
            ['connected_site' => ['site_script' => ['url' => 'https://chimpstatic.com/mcjs/x.js']]]
        );

        $this->assertSame('https://chimpstatic.com/mcjs/x.js', $helper->getJsUrl(1));
        $this->assertSame(1, $this->apiCalls);
        $this->assertSame(
            ['mailchimp/general/mailchimpjsurl' => 'https://chimpstatic.com/mcjs/x.js'],
            $this->saved
        );
    }

    public function testAnAlreadyResolvedUrlAsksNothing()
    {
        $helper = $this->helper('abc123', 'https://chimpstatic.com/mcjs/x.js');

        $this->assertSame('https://chimpstatic.com/mcjs/x.js', $helper->getJsUrl(1));
        $this->assertSame(0, $this->apiCalls);
    }

    /**
     * A real store id that fails is still one call. Not remembering that is a
     * separate problem and deliberately not fixed here.
     */
    public function testARealStoreThatFailsIsLoggedAndReturnsEmpty()
    {
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'));

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame(1, $this->apiCalls);
        $this->assertSame([], $this->saved);
    }
}
