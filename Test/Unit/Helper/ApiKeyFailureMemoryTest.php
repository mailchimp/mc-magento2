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

class ApiKeyFailureMemoryTest extends TestCase
{
    /**
     * The helper takes two dozen constructor arguments, none of which this
     * behaviour touches. Only getApiKey() is stubbed, so the code under test
     * is the real thing.
     *
     * @param  array $keysByStore
     * @return MailChimpHelper
     */
    private function helperWithKeys(array $keysByStore)
    {
        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getApiKey'])
            ->getMock();

        $helper->method('getApiKey')->willReturnCallback(
            function ($store = null, $scope = null) use ($keysByStore) {
                return isset($keysByStore[$store]) ? $keysByStore[$store] : '';
            }
        );

        return $helper;
    }

    public function testAStoreIsNotFailedBeforeAnythingFails()
    {
        $helper = $this->helperWithKeys([1 => 'key-a-us1']);

        $this->assertFalse($helper->isApiKeyFailed(1));
    }

    public function testFailureIsRememberedForTheStoreThatFailed()
    {
        $helper = $this->helperWithKeys([1 => 'key-a-us1']);
        $helper->markApiKeyFailed(1);

        $this->assertTrue($helper->isApiKeyFailed(1));
    }

    /**
     * The point of the whole change: one credential shared across many store
     * views is answered once, not once per view.
     */
    public function testFailureIsSharedByEveryStoreUsingTheSameKey()
    {
        $helper = $this->helperWithKeys([1 => 'key-a-us1', 2 => 'key-a-us1', 3 => 'key-a-us1']);
        $helper->markApiKeyFailed(1);

        $this->assertTrue($helper->isApiKeyFailed(2));
        $this->assertTrue($helper->isApiKeyFailed(3));
    }

    /**
     * And the converse, so the memory cannot silence a store it knows nothing
     * about: a different key is a different credential.
     */
    public function testADifferentKeyIsUnaffected()
    {
        $helper = $this->helperWithKeys([1 => 'key-a-us1', 2 => 'key-b-us2']);
        $helper->markApiKeyFailed(1);

        $this->assertFalse($helper->isApiKeyFailed(2));
    }

    /**
     * An unconfigured store has no credential to blame. Without this, every
     * empty key would share one fingerprint and the first unconfigured store
     * would silence all the others.
     */
    public function testAnEmptyKeyIsNeverRemembered()
    {
        $helper = $this->helperWithKeys([1 => '', 2 => '   ', 3 => 'key-a-us1']);
        $helper->markApiKeyFailed(1);

        $this->assertFalse($helper->isApiKeyFailed(1));
        $this->assertFalse($helper->isApiKeyFailed(2));
        $this->assertFalse($helper->isApiKeyFailed(3));
    }

    /**
     * Whitespace and case are the same credential as far as Mailchimp is
     * concerned, but not as far as a naive array key is.
     */
    public function testKeyIsMatchedAfterTrimming()
    {
        $helper = $this->helperWithKeys([1 => 'key-a-us1', 2 => '  key-a-us1  ']);
        $helper->markApiKeyFailed(2);

        $this->assertTrue($helper->isApiKeyFailed(1));
    }
}
