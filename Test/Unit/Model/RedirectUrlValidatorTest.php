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

namespace Ebizmarts\MailChimp\Test\Unit\Model;

use Ebizmarts\MailChimp\Model\RedirectUrlValidator;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Open-redirect (CWE-601) guard tests. The store's own host is example.com;
 * everything that resolves to another host must be rejected.
 */
class RedirectUrlValidatorTest extends TestCase
{
    /**
     * @var RedirectUrlValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->validator = new RedirectUrlValidator($storeManager);
    }

    /**
     * @dataProvider safeUrlProvider
     */
    public function testSafeUrlsAreAccepted($url)
    {
        $this->assertTrue($this->validator->isSafe($url, 1));
    }

    public static function safeUrlProvider()
    {
        return [
            'relative path'         => ['/checkout/cart/'],
            'relative with query'   => ['/mailchimp/cart/loadquote/id/5/'],
            'same host absolute'    => ['https://example.com/checkout/'],
            'same host http'        => ['http://example.com/checkout/'],
        ];
    }

    /**
     * @dataProvider unsafeUrlProvider
     */
    public function testUnsafeUrlsAreRejected($url)
    {
        $this->assertFalse($this->validator->isSafe($url, 1));
    }

    public static function unsafeUrlProvider()
    {
        return [
            'foreign host'          => ['https://evil.com/'],
            'protocol relative'     => ['//evil.com/'],
            'backslash bypass'      => ['/\\evil.com'],
            'scheme backslash'      => ['https:\\\\evil.com'],
            'javascript scheme'     => ['javascript:alert(1)'],
            'data scheme'           => ['data:text/html,<script>1</script>'],
            'empty'                 => [''],
            'null'                  => [null],
        ];
    }

    public function testGetSafeUrlReturnsOriginalWhenSafe()
    {
        $url = 'https://example.com/checkout/';
        $this->assertSame($url, $this->validator->getSafeUrl($url, 1));
    }

    public function testGetSafeUrlFallsBackToBaseUrlWhenUnsafe()
    {
        $this->assertSame(
            'https://example.com/',
            $this->validator->getSafeUrl('https://evil.com/phish', 1)
        );
    }
}
