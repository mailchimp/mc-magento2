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
 * The merge-field map, which is per store view and used to be per process.
 *
 * `Cron/Ecommerce` loops every store view with one helper instance, and the
 * subscriber sync reaches this through getMergeVarsBySubscriber(). A memo that
 * ignored the store id meant every view after the first synchronised with the
 * first view's map, writing wrong merge fields to Mailchimp.
 */
class MapFieldsTest extends TestCase
{
    /** @var int how many times the configuration was read */
    private $reads = 0;

    /**
     * @param  array $perStore  storeId => serialised map
     * @return MailChimpHelper
     */
    private function helper(array $perStore)
    {
        $this->reads = 0;
        $test = $this;

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigValue', 'unserialize', 'log'])
            ->getMock();

        $helper->method('getConfigValue')->willReturnCallback(
            function ($path, $storeId = null) use ($perStore, $test) {
                $test->countRead();

                return isset($perStore[$storeId]) ? $perStore[$storeId] : null;
            }
        );
        $helper->method('unserialize')->willReturnCallback(
            function ($value) { return $value === null ? null : json_decode($value, true); }
        );

        // getBindableAttributes() is private and reads customer attributes, so
        // the attribute table goes in by hand. Ids are what the stored map is
        // keyed by.
        $attributes = [
            10 => ['attCode' => 'firstname', 'isDate' => false, 'isAddress' => false, 'options' => ['a']],
            11 => ['attCode' => 'lastname',  'isDate' => false, 'isAddress' => false, 'options' => ['b']],
        ];
        $property = new \ReflectionProperty(MailChimpHelper::class, 'customerAtt');
        $property->setAccessible(true);
        $property->setValue($helper, $attributes);

        return $helper;
    }

    public function countRead()
    {
        $this->reads++;
    }

    /**
     * @param  MailChimpHelper $helper
     * @param  mixed           $storeId
     * @param  bool            $options
     * @return array
     */
    private function map($helper, $storeId, $options = true)
    {
        return $helper->getMapFields($storeId, $options);
    }

    /**
     * The defect, stated as the case it produced: two store views with
     * different maps, read in the order the cron reads them.
     */
    public function testEachStoreViewGetsItsOwnMap()
    {
        $helper = $this->helper([
            1 => json_encode([10 => 'FNAME']),
            2 => json_encode([11 => 'LNAME']),
        ]);

        $first  = $this->map($helper, 1);
        $second = $this->map($helper, 2);

        $this->assertSame('firstname', $first[0]['customer_field']);
        $this->assertSame(
            'lastname',
            $second[0]['customer_field'],
            'the second store view was served the first one\'s map'
        );
    }

    /**
     * `$options` changes what each entry carries, and `bin/magento cron:run`
     * runs every due job of a group in one process, so both variants are
     * reachable from one helper instance.
     */
    public function testTheOptionsVariantIsNotServedToTheOtherCaller()
    {
        $helper = $this->helper([1 => json_encode([10 => 'FNAME'])]);

        $withOptions    = $this->map($helper, 1, true);
        $withoutOptions = $this->map($helper, 1, false);

        $this->assertSame(['a'], $withOptions[0]['options']);
        $this->assertFalse($withoutOptions[0]['options']);
    }

    /**
     * An install with nothing mapped produces an empty array, which is falsy.
     * The old condition treated that as "not loaded yet", so the memo never
     * fired in the case it is cheapest to serve — re-reading the configuration
     * for every subscriber in the batch.
     */
    public function testAnEmptyMapIsStillRemembered()
    {
        $helper = $this->helper([]);

        $this->assertSame([], $this->map($helper, 1));
        $readsAfterFirst = $this->reads;

        $this->map($helper, 1);
        $this->map($helper, 1);

        $this->assertSame($readsAfterFirst, $this->reads, 'an empty map is being re-read on every call');
    }

    /**
     * And the memo still does its job for a map that has something in it.
     */
    public function testAMapIsReadOncePerStoreView()
    {
        $helper = $this->helper([1 => json_encode([10 => 'FNAME'])]);

        $this->map($helper, 1);
        $readsAfterFirst = $this->reads;
        $this->map($helper, 1);

        $this->assertSame($readsAfterFirst, $this->reads);
    }

    /**
     * Callers iterate the result. It was initialised to null, so an install
     * with nothing mapped handed back null.
     */
    public function testTheResultIsAlwaysIterable()
    {
        $helper = $this->helper([]);

        $this->assertIsArray($this->map($helper, 1));
    }
}
