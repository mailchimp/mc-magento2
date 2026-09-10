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
     * @param  mixed $cache     cache double, or null for a helper built without one
     * @return MailChimpHelper
     */
    private function helper($storeId, $savedUrl, $answer = null, $cache = null)
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

        // The constructor is disabled, so the cache has to go in by hand. A
        // helper left without one is the pre-upgrade object: it is what proves
        // the new argument stayed optional.
        if ($cache !== null) {
            $property = new \ReflectionProperty(MailChimpHelper::class, '_cache');
            $property->setAccessible(true);
            $property->setValue($helper, $cache);
        }

        return $helper;
    }

    /**
     * An in-memory CacheInterface.
     *
     * clean() honours tags rather than emptying itself, so a production call
     * that cleaned the wrong tag would fail here instead of passing on a double
     * that forgives it.
     *
     * @return object
     */
    private function cache()
    {
        return new class implements \Magento\Framework\App\CacheInterface {
            /** @var array */
            public $data = [];
            /** @var array */
            public $saves = [];
            /** @var array */
            public $cleans = [];

            public function getFrontend()
            {
                return null;
            }

            public function load($identifier)
            {
                return array_key_exists($identifier, $this->data) ? $this->data[$identifier] : false;
            }

            public function save($data, $identifier, $tags = [], $lifeTime = null)
            {
                $this->data[$identifier] = (string)$data;
                $this->saves[] = ['id' => $identifier, 'tags' => $tags, 'lifetime' => $lifeTime];

                return true;
            }

            public function remove($identifier)
            {
                unset($this->data[$identifier]);

                return true;
            }

            public function clean($tags = [])
            {
                $this->cleans[] = $tags;
                foreach ($this->saves as $save) {
                    if (array_intersect((array)$tags, $save['tags'])) {
                        unset($this->data[$save['id']]);
                    }
                }

                return true;
            }
        };
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
     * A real store id that fails writes nothing and returns empty. What it must
     * not do is keep asking -- see the negative-cache tests below.
     */
    public function testARealStoreThatFailsIsLoggedAndReturnsEmpty()
    {
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'));

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame(1, $this->apiCalls);
        $this->assertSame([], $this->saved);
    }

    /**
     * Bug 3: a store id that is real but does not resolve passes the guards and
     * fails, saving nothing, so every uncached render asked again.
     *
     * These four are the ways to get there. The first three are merchant state;
     * the fourth is not, and is the reason the others are worth fixing with it.
     *
     * @return array
     */
    public static function unresolvableStoreProvider()
    {
        return [
            'deleted on Mailchimp\'s side, or a database restored against another account' => [
                new \Mailchimp_Error('/ecommerce/stores/abc123', 'GET', '', 'Resource Not Found', 'nope'),
            ],
            'the key was revoked' => [
                new \Mailchimp_Error('/ecommerce/stores/abc123', 'GET', '', 'API Key Invalid', 'nope'),
            ],
            'Mailchimp is down or slow: curl gives up, and the visitor waited for it' => [
                new \Mailchimp_HttpError(
                    '/ecommerce/stores/abc123',
                    'GET',
                    '',
                    '',
                    'Operation timed out after 10001 milliseconds'
                ),
            ],
            'the store exists but has no connected site: a 200 carrying no URL' => [
                ['id' => 'abc123', 'name' => 'Store', 'connected_site' => []],
            ],
        ];
    }

    /**
     * @dataProvider unresolvableStoreProvider
     * @param mixed $answer
     */
    public function testAnUnresolvableStoreIsAskedOnceNotOncePerRender($answer)
    {
        $cache  = $this->cache();
        $helper = $this->helper('abc123', '', $answer, $cache);

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame('', $helper->getJsUrl(1));

        $this->assertSame(1, $this->apiCalls);
        $this->assertSame([], $this->saved);
    }

    /**
     * The marker carries the documented lifetime and the tag the admin paths
     * clean by. Neither is decoration: a missing lifetime would remember the
     * failure until the cache was flushed, and a missing tag would leave
     * clearJsUrlFailures() nothing to find.
     */
    public function testTheMarkerCarriesTheLifetimeAndTheTag()
    {
        $cache  = $this->cache();
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'), $cache);

        $helper->getJsUrl(1);

        $this->assertCount(1, $cache->saves);
        $this->assertSame(MailChimpHelper::JS_URL_FAILURE_TTL, $cache->saves[0]['lifetime']);
        $this->assertSame([MailChimpHelper::JS_URL_FAILURE_CACHE_TAG], $cache->saves[0]['tags']);
    }

    /**
     * Store views resolve to different Mailchimp stores, so one failing view
     * must not silence a working one.
     */
    public function testTheMarkerIsPerStoreView()
    {
        $cache  = $this->cache();
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'), $cache);

        $helper->getJsUrl(1);
        $helper->getJsUrl(2);

        $this->assertSame(2, $this->apiCalls);

        $helper->getJsUrl(1);
        $helper->getJsUrl(2);

        $this->assertSame(2, $this->apiCalls);
    }

    public function testASuccessfulLookupLeavesNoMarker()
    {
        $cache  = $this->cache();
        $helper = $this->helper(
            'abc123',
            '',
            ['connected_site' => ['site_script' => ['url' => 'https://chimpstatic.com/mcjs/x.js']]],
            $cache
        );

        $this->assertSame('https://chimpstatic.com/mcjs/x.js', $helper->getJsUrl(1));
        $this->assertSame([], $cache->saves);
    }

    /**
     * What the "Fix Mailchimp JS" button and a change of Mailchimp store call.
     * Without it those two would clear the config value and appear not to have
     * worked, because the marker would still be telling the helper not to ask.
     */
    public function testClearingTheMarkersMakesTheNextRenderAskAgain()
    {
        $cache  = $this->cache();
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'), $cache);

        $helper->getJsUrl(1);
        $helper->getJsUrl(1);
        $this->assertSame(1, $this->apiCalls);

        $helper->clearJsUrlFailures();

        $this->assertSame([[MailChimpHelper::JS_URL_FAILURE_CACHE_TAG]], $cache->cleans);

        $helper->getJsUrl(1);
        $this->assertSame(2, $this->apiCalls);
    }

    /**
     * The cache is a new, optional, trailing constructor argument. A helper
     * built without one -- a stale generated factory, an override that still
     * lists the old arguments -- has to keep working, calling every render as
     * it did before, rather than fatal on a null.
     */
    public function testAHelperBuiltWithoutACacheStillWorks()
    {
        $helper = $this->helper('abc123', '', new \Mailchimp_Error('/', 'GET', '', 'Not Found', 'nope'));

        $this->assertSame('', $helper->getJsUrl(1));
        $this->assertSame('', $helper->getJsUrl(1));

        $this->assertSame(2, $this->apiCalls);

        $helper->clearJsUrlFailures();
    }

    /**
     * The two halves of how the cache reaches the helper, pinned together
     * because either one alone is a helper that never caches anything.
     *
     * The argument has a default so the constructor stays backwards compatible.
     * The price of that default is that the ObjectManager will not resolve it:
     * ClassReader records a parameter with a default value as not required
     * (Code/Reader/ClassReader.php), and AbstractFactory::getResolvedArgument()
     * hands a not-required parameter its default instead of instantiating its
     * type. So di.xml has to name it, and every test above would still pass if
     * nobody did -- they inject the cache themselves.
     */
    public function testTheCacheIsOptionalInTheConstructorAndThereforeWiredInDi()
    {
        $cache = null;
        foreach ((new \ReflectionClass(MailChimpHelper::class))->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getName() === 'cache') {
                $cache = $parameter;
            }
        }

        $this->assertNotNull($cache, 'The helper no longer takes a $cache argument.');
        $this->assertTrue(
            $cache->isDefaultValueAvailable(),
            'Dropping the default makes the argument required, which breaks anything still '
            . 'calling the constructor with the old argument list.'
        );

        $di = simplexml_load_file(__DIR__ . '/../../../etc/di.xml');
        $argument = $di->xpath(
            '//type[@name="Ebizmarts\MailChimp\Helper\Data"]/arguments/argument[@name="cache"]'
        );

        $this->assertCount(
            1,
            $argument,
            'di.xml does not name the $cache argument, so it arrives null and getJsUrl() calls '
            . 'the API on every render again.'
        );
        $this->assertSame('Magento\Framework\App\CacheInterface', trim((string)$argument[0]));
    }
}
