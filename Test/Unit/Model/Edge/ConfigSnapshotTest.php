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
use Ebizmarts\MailChimp\Model\Edge\ConfigSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * The configuration snapshot that replaces the support-log lane.
 *
 * Two things are pinned here that the lane it replaces got wrong, and one that
 * the receiving side cannot protect us from.
 */
class ConfigSnapshotTest extends TestCase
{
    /** @var array every (path, storeId) the snapshot asked for */
    private $asked = [];

    /**
     * @param  array $values  config path => value
     * @return ConfigSnapshot
     */
    private function snapshot(array $values)
    {
        $this->asked = [];
        $test = $this;

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfigValue'])
            ->getMock();

        $helper->method('getConfigValue')->willReturnCallback(
            function ($path, $storeId = null) use ($values, $test) {
                $test->recordAsk($path, $storeId);

                return array_key_exists($path, $values) ? $values[$path] : null;
            }
        );

        return new ConfigSnapshot($helper);
    }

    /**
     * @param string $path
     * @param mixed  $storeId
     */
    public function recordAsk($path, $storeId)
    {
        $this->asked[$path] = $storeId;
    }

    /**
     * The receiving whitelist takes a type and a cap per key and has no path
     * into a nested object, so anything nested would arrive, be accepted, and
     * be dropped.
     */
    public function testEveryValueIsAScalar()
    {
        $snapshot = $this->snapshot([
            MailChimpHelper::XML_POPUP_FORM          => 1,
            MailChimpHelper::XML_PATH_WEBHOOK_ACTIVE => 1,
        ])->forStore(1);

        $this->assertNotEmpty($snapshot);
        foreach ($snapshot as $key => $value) {
            $this->assertTrue(
                $value === null || is_scalar($value),
                "$key is not a scalar, so the receiver would drop it"
            );
        }
    }

    /**
     * @return array
     */
    public static function storeScopedProvider()
    {
        return [
            // The two the old lane read at default scope while every other
            // line in the same block passed the store id. A multi-store-view
            // install reported the default's value on every view.
            'the webhook delete action' => [MailChimpHelper::XML_PATH_WEBHOOK_DELETE],
            'whether groups are shown'  => [MailChimpHelper::XML_INTEREST_IN_SUCCESS],
            // And a control from the same block, which was always right.
            'the audience'              => [MailChimpHelper::XML_PATH_LIST],
        ];
    }

    /**
     * @dataProvider storeScopedProvider
     * @param string $path
     */
    public function testEverySettingIsReadAtTheStoreView($path)
    {
        $this->snapshot([MailChimpHelper::XML_PATH_WEBHOOK_ACTIVE => 1])->forStore(7);

        $this->assertArrayHasKey($path, $this->asked, "$path was never read");
        $this->assertSame(7, $this->asked[$path], "$path was read at the wrong scope");
    }

    /**
     * The receiver truncates an over-length string rather than refusing it, so
     * merchant HTML would be stored as a fragment ending mid-tag: something
     * that looks like content, is not, and reads as real a year later.
     */
    public function testMerchantHtmlIsReportedAsPresenceNeverAsContent()
    {
        $html = '<p>' . str_repeat('a', 2000) . '</p>';
        $snapshot = $this->snapshot([
            MailChimpHelper::XML_INTEREST_SUCCESS_HTML_BEFORE => $html,
            MailChimpHelper::XML_INTEREST_SUCCESS_HTML_AFTER  => '   ',
        ])->forStore(1);

        $this->assertTrue($snapshot['cfg_group_description_set']);
        $this->assertFalse($snapshot['cfg_success_message_set'], 'whitespace is not a customisation');

        foreach ($snapshot as $value) {
            $this->assertStringNotContainsString(
                'aaaa',
                (string)$value,
                'the snapshot is carrying merchant-authored text'
            );
        }
    }

    /**
     * A popup url with no popup, or a delete action with no webhook, describes
     * nothing that is in effect and would be read as if it were.
     */
    public function testSettingsThatDependOnAToggleAreAbsentWhenItIsOff()
    {
        $off = $this->snapshot([])->forStore(1);
        $this->assertArrayNotHasKey('cfg_popup_url', $off);
        $this->assertArrayNotHasKey('cfg_delete_action', $off);

        $on = $this->snapshot([
            MailChimpHelper::XML_POPUP_FORM          => 1,
            MailChimpHelper::XML_POPUP_URL           => 'https://example.test/popup',
            MailChimpHelper::XML_PATH_WEBHOOK_ACTIVE => 1,
            MailChimpHelper::XML_PATH_WEBHOOK_DELETE => '2',
        ])->forStore(1);
        $this->assertSame('https://example.test/popup', $on['cfg_popup_url']);
        $this->assertSame('2', $on['cfg_delete_action']);
    }

    /**
     * An unset setting and an empty one are the same thing to anyone reading
     * this, and a key costs a column on every row of every install.
     */
    public function testAnEmptySettingIsNullRatherThanAnEmptyString()
    {
        $snapshot = $this->snapshot([MailChimpHelper::XML_MAILCHIMP_STORE => ''])->forStore(1);

        $this->assertNull($snapshot['cfg_store']);
        $this->assertNull($snapshot['cfg_timeout']);
    }

    /**
     * Types are what the value is, not what the config stores. Magento returns
     * config as strings, and `"0"` is a real setting rather than an absent one.
     */
    public function testTypesAreWhatTheValueIs()
    {
        $snapshot = $this->snapshot([
            MailChimpHelper::XML_PATH_ACTIVE       => '1',
            MailChimpHelper::XML_PATH_ECOMMERCE_ACTIVE => '0',
            MailChimpHelper::XML_PATH_TIMEOUT      => '30',
        ])->forStore(1);

        $this->assertTrue($snapshot['cfg_active']);
        $this->assertFalse($snapshot['cfg_ecommerce']);
        $this->assertSame(30, $snapshot['cfg_timeout']);
    }
}
