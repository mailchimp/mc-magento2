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
 * What the library is told about the process it is reporting for.
 *
 * Every case here is a way the surface can be unavailable rather than wrong,
 * because unavailable is the normal state on at least one lane: a cron has no
 * dispatch, an early request has no area, and an app/code installation can be
 * running a library that has never heard of any of this.
 */
class SurfaceTest extends TestCase
{
    /**
     * The API double, recording what it was told.
     *
     * @param  bool $supportsSurface  false models a library older than the method
     * @return object
     */
    private function api($supportsSurface = true)
    {
        if ($supportsSurface) {
            return new class {
                public $calls = [];
                public function setSurface($area, $action) { $this->calls[] = [$area, $action]; }
            };
        }

        return new class {
            public $calls = [];
        };
    }

    /**
     * A request double built from its three segments.
     *
     * getFullActionName() concatenates rather than formats, exactly as the
     * platform's does, so a segment that is not a string arrives here as
     * whatever PHP makes of it -- which is the case these tests exist for.
     *
     * @param  mixed $segments  [route, controller, action], or null for a
     *                          request object without the methods at all
     * @return object
     */
    private function request($segments)
    {
        if ($segments === null) {
            return new class {
                public function getParam($k) { return null; }
            };
        }

        return new class($segments) {
            private $segments;
            public function __construct($segments) { $this->segments = $segments; }
            public function getRouteName() { return $this->segments[0]; }
            public function getControllerName() { return $this->segments[1]; }
            public function getActionName() { return $this->segments[2]; }
            public function getFullActionName()
            {
                return $this->segments[0] . '_' . $this->segments[1] . '_' . $this->segments[2];
            }
        };
    }

    /**
     * @param  mixed $area  area code, or a throwable for "no area set yet"
     * @return object
     */
    private function state($area)
    {
        return new class($area) {
            private $area;
            public function __construct($area) { $this->area = $area; }
            public function getAreaCode()
            {
                if ($this->area instanceof \Throwable) { throw $this->area; }
                return $this->area;
            }
        };
    }

    /**
     * @param  object $api
     * @param  object $request
     * @param  mixed  $state  state double, or null for a helper built without one
     * @return MailChimpHelper
     */
    private function helper($api, $request, $state)
    {
        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['log'])
            ->getMock();

        foreach (['_api' => $api, '_request' => $request, '_state' => $state] as $name => $value) {
            $property = new \ReflectionProperty(MailChimpHelper::class, $name);
            $property->setAccessible(true);
            $property->setValue($helper, $value);
        }

        return $helper;
    }

    /**
     * @param  MailChimpHelper $helper
     * @return void
     */
    private function apply($helper)
    {
        $method = new \ReflectionMethod(MailChimpHelper::class, 'applySurface');
        $method->setAccessible(true);
        $method->invoke($helper);
    }

    public function testAWebDispatchIsReportedWhole()
    {
        $api = $this->api();
        $request = $this->request(['mailchimp', 'campaign', 'check']);
        $this->apply($this->helper($api, $request, $this->state('frontend')));

        $this->assertSame([['frontend', 'mailchimp_campaign_check']], $api->calls);
    }

    /**
     * The pair this field exists to separate: both sites reach the campaigns
     * family and both emit an identical campaigns:404 into one last-write-wins
     * slot, so until now nothing could tell the storefront's benign check from
     * the merchant's own click.
     */
    public function testTheAdminCallSiteIsDistinguishableFromTheStorefrontOne()
    {
        $admin = $this->api();
        $adminRequest = $this->request(['mailchimp', 'orders', 'campaign']);
        $this->apply($this->helper($admin, $adminRequest, $this->state('adminhtml')));

        $store = $this->api();
        $storeRequest = $this->request(['mailchimp', 'campaign', 'check']);
        $this->apply($this->helper($store, $storeRequest, $this->state('frontend')));

        $this->assertNotSame($admin->calls, $store->calls);
    }

    /**
     * A cron has no dispatch, so the three segments are null and
     * getFullActionName() returns the bare delimiters.
     *
     * `__` passes the library's charset rule and the receiver's -- underscore
     * is in both -- so without this filter every cron run would report an
     * action name, and it would be the same meaningless one on every
     * installation in the fleet.
     */
    public function testTheBareDelimitersAreNotAnActionName()
    {
        $api = $this->api();
        $this->apply($this->helper($api, $this->request([null, null, null]), $this->state('crontab')));

        $this->assertSame([['crontab', '']], $api->calls);
    }

    /**
     * And the other half of that rule, which is why it is written as "carries
     * something that is not a separator" rather than as anything about
     * underscores: the route resolved and the controller and action did not.
     * That is a true thing about the dispatch and it is worth reporting.
     */
    public function testARouteWithTheRestUnroutedIsStillWorthReporting()
    {
        $api = $this->api();
        $this->apply($this->helper($api, $this->request(['mailchimp', null, null]), $this->state('frontend')));

        $this->assertSame([['frontend', 'mailchimp__']], $api->calls);
    }

    /**
     * @return array
     */
    public static function nonStringSegmentProvider()
    {
        return [
            'integers'          => [[1, 2, 3], '1_2_3'],
            'booleans'          => [[true, true, true], '1_1_1'],
            'one bad segment'   => [['mailchimp', 2, 3], 'mailchimp_2_3'],
            'a float'           => [[1.5, 2.5, 3.5], '1.5_2.5_3.5'],
        ];
    }

    /**
     * The check that can only be made here.
     *
     * getFullActionName() is three values concatenated and nothing constrains
     * them to strings -- setRouteName() and its siblings take what they are
     * handed, and the concatenation turns it into an ordinary string on the
     * way out. Measured on framework 103.0.8 / PHP 8.3, integers compose
     * `1_2_3` and booleans compose `1_1_1`.
     *
     * Both are ASCII and both carry something that is not a separator, so
     * every rule downstream accepts them -- and both name a route that has
     * never existed anywhere. By the time the value leaves this method it is
     * an unremarkable string and nothing can tell it from a real one.
     *
     * @dataProvider nonStringSegmentProvider
     * @param array  $segments
     * @param string $composed  what those segments compose, for the record
     */
    public function testASegmentThatIsNotAStringIsNotADispatchWeCanName($segments, $composed)
    {
        $request = $this->request($segments);
        $this->assertSame($composed, $request->getFullActionName(), 'the double no longer composes what the platform does');

        $api = $this->api();
        $this->apply($this->helper($api, $request, $this->state('frontend')));

        $this->assertSame([['frontend', '']], $api->calls);
    }

    /**
     * @return array
     */
    public static function emptyActionProvider()
    {
        return [
            'bare delimiters' => [[null, null, null]],
            'empty strings'   => [['', '', '']],
            'no methods'      => [null],
        ];
    }

    /**
     * @dataProvider emptyActionProvider
     * @param mixed $action
     */
    public function testAnActionWithNothingInItIsNotReported($action)
    {
        $api = $this->api();
        $this->apply($this->helper($api, $this->request($action), $this->state('crontab')));

        $this->assertSame([['crontab', '']], $api->calls);
    }

    /**
     * Not an error: nothing has set an area yet. Reporting no area is the
     * right answer, and failing the API call over it is not.
     */
    public function testAnAreaThatIsNotSetYetIsReportedAsAbsent()
    {
        $api = $this->api();
        $state = $this->state(new \Magento\Framework\Exception\LocalizedException(__('Area code is not set')));
        $this->apply($this->helper($api, $this->request(['checkout', 'index', 'index']), $state));

        $this->assertSame([['', 'checkout_index_index']], $api->calls);
    }

    /**
     * DI resolves an interceptor here, and a plugin on getAreaCode() can throw
     * anything at all. The principle is about what naming the surface is
     * allowed to cost -- never the API call -- not about which exception class
     * the platform happens to use.
     */
    public function testAnAreaThatThrowsSomethingElseCostsNothingEither()
    {
        $api = $this->api();
        $this->apply($this->helper($api, $this->request(['checkout', 'index', 'index']), $this->state(new \RuntimeException('plugin'))));

        $this->assertSame([['', 'checkout_index_index']], $api->calls);
    }

    /**
     * The helper can be constructed without a state -- the argument has a
     * default, so anything calling the old argument list still works.
     */
    public function testAHelperWithNoStateStillReportsTheAction()
    {
        $api = $this->api();
        $this->apply($this->helper($api, $this->request(['checkout', 'index', 'index']), null));

        $this->assertSame([['', 'checkout_index_index']], $api->calls);
    }

    /**
     * An app/code installation pairs whichever library is present with
     * whichever module is present, so the version constraint in composer.json
     * decides nothing there. Without the guard this is a fatal on every call
     * into the API, which is most of the extension.
     */
    public function testALibraryWithoutTheMethodIsNotCalled()
    {
        $api = $this->api(false);
        $this->apply($this->helper($api, $this->request(['checkout', 'index', 'index']), $this->state('frontend')));

        $this->assertSame([], $api->calls);
    }

    /**
     * The same pairing as the cache argument, and for the same reason: the
     * default keeps the constructor backwards compatible, and the price of the
     * default is that the ObjectManager hands the parameter its default rather
     * than instantiating its type. So di.xml has to name it -- and every test
     * above would still pass if nobody did, since they set the property by
     * hand.
     */
    public function testTheStateIsOptionalInTheConstructorAndThereforeWiredInDi()
    {
        $state = null;
        foreach ((new \ReflectionClass(MailChimpHelper::class))->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getName() === 'state') {
                $state = $parameter;
            }
        }

        $this->assertNotNull($state, 'The helper no longer takes a $state argument.');
        $this->assertTrue(
            $state->isDefaultValueAvailable(),
            'Dropping the default makes the argument required, which breaks anything still '
            . 'calling the constructor with the old argument list.'
        );

        $di = simplexml_load_file(__DIR__ . '/../../../etc/di.xml');
        $argument = $di->xpath(
            '//type[@name="Ebizmarts\MailChimp\Helper\Data"]/arguments/argument[@name="state"]'
        );

        $this->assertCount(
            1,
            $argument,
            'di.xml does not name the $state argument, so it arrives null and every beacon '
            . 'reports without an area.'
        );
        $this->assertSame('Magento\Framework\App\State', trim((string)$argument[0]));
    }
}
