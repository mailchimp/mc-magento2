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
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use PHPUnit\Framework\TestCase;

class InterestGroupsTest extends TestCase
{
    /** @var int */
    private $apiCalls = 0;

    /**
     * @param  string $groups  value of mailchimp/general/interest
     * @param  mixed  $listId  value of mailchimp/general/monkeylist
     * @return MailChimpHelper
     */
    private function helper($groups, $listId)
    {
        $this->apiCalls = 0;

        // The real call is two deep: the categories, then one request per
        // configured group id.
        $interests = new class($this) {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function getAll($listId, $catId, $a = null, $b = null, $c = null)
            {
                $this->test->countCall();
                return ['interests' => []];
            }
        };
        $category = new class($this, $interests) {
            private $test;
            public $interests;
            public function __construct($test, $interests) { $this->test = $test; $this->interests = $interests; }
            public function getAll($listId, $a = null, $b = null, $c = null)
            {
                $this->test->countCall();
                return ['categories' => []];
            }
        };
        $lists = new \stdClass();
        $lists->interestCategory = $category;
        $api = new \stdClass();
        $api->lists = $lists;

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigValue', 'getApi', 'log'])
            ->getMock();
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path) use ($groups, $listId) {
                if ($path === MailChimpHelper::XML_INTEREST)   { return $groups; }
                if ($path === MailChimpHelper::XML_PATH_LIST)  { return $listId; }
                return null;
            }
        );
        $helper->method('getApi')->willReturn($api);

        return $helper;
    }

    public function countCall()
    {
        $this->apiCalls++;
    }

    /**
     * getSubscriberInterest() reaches for the stored group data after the
     * sentinel, so the factory has to exist for the sentinel to be reachable
     * at all.
     *
     * @param  MailChimpHelper $helper
     * @return void
     */
    private function giveItAGroupStore(MailChimpHelper $helper)
    {
        // getGroupdata() is a magic getter on the Magento model rather than a
        // declared method, so it cannot be configured on a mock.
        $group = new class {
            public function getBySubscriberIdStoreId($subscriberId, $storeId) { return $this; }
            public function getGroupdata() { return null; }
        };

        $factory = $this->createMock(MailChimpInterestGroupFactory::class);
        $factory->method('create')->willReturn($group);

        $prop = new \ReflectionProperty(MailChimpHelper::class, '_interestGroupFactory');
        $prop->setAccessible(true);
        $prop->setValue($helper, $factory);
    }

    /**
     * The silent majority: an audience configured and no groups chosen. The
     * answer cannot be used either way -- the first loop matches against an
     * empty list and the second iterates one -- so the call was pure waste,
     * on a 200, with no error to notice it by.
     */
    public function testNoGroupsConfiguredAsksNothing()
    {
        $helper = $this->helper('', 'list-1');

        $this->assertSame([], $helper->getInterest(1));
        $this->assertSame(0, $this->apiCalls);
    }

    public function testAnUnsetAudienceAsksNothing()
    {
        foreach (['', null, 0, '0', -1, '-1'] as $noAudience) {
            $helper = $this->helper('cat-1', $noAudience);
            $this->assertSame([], $helper->getInterest(1));
            $this->assertSame(0, $this->apiCalls, var_export($noAudience, true));
        }
    }

    public function testAConfiguredStoreStillAsks()
    {
        $helper = $this->helper('cat-1', 'list-1');

        // One for the categories, one per configured group id.
        $helper->getInterest(1);
        $this->assertSame(2, $this->apiCalls);
    }

    /**
     * [] is an answer. Read as falsy it meant "nothing was supplied", so the
     * helper re-asked Mailchimp once per subscriber for a store that simply
     * has no groups.
     */
    public function testAnEmptyResultSuppliedByTheCallerIsNotRefetched()
    {
        $helper = $this->helper('cat-1', 'list-1');
        $this->giveItAGroupStore($helper);

        $helper->getSubscriberInterest(1, 1, []);
        $this->assertSame(0, $this->apiCalls);
    }

    public function testNothingSuppliedIsStillFetched()
    {
        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getInterest'])
            ->getMock();
        $helper->expects($this->once())->method('getInterest')->willReturn([]);
        $this->giveItAGroupStore($helper);

        $helper->getSubscriberInterest(1, 1, null);
    }
}
