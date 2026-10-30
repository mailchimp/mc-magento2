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

namespace Ebizmarts\MailChimp\Test\Unit\Controller\WebHook;

use PHPUnit\Framework\TestCase;

/**
 * How the inbound webhook authenticates.
 *
 * The `wkey` query parameter is the only credential this endpoint has: it is a
 * webhook, so there is no form key and CSRF validation is deliberately
 * bypassed. That makes the comparison of that one value the whole of the
 * authentication, and leaves two properties of it worth pinning.
 *
 * This reads the source rather than executing the controller, for the reason
 * the admin controller test next door gives: constructing a Magento action
 * needs a full context, while what matters here is a single expression that is
 * one careless edit away from being weakened again.
 */
class IndexTest extends TestCase
{
    /** @var string */
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../../../Controller/WebHook/Index.php';
        $source = file_get_contents($path);

        $this->assertNotFalse($source, "cannot read $path");
        $this->source = $source;
    }

    public function testTheKeyIsComparedInConstantTime(): void
    {
        $this->assertStringContainsString(
            'hash_equals($key, $requestKey)',
            $this->source,
            'the webhook key must be compared with hash_equals()'
        );
    }

    /**
     * A comparison operator on a shared secret is what this replaced, and it is
     * the shape that would come back if someone simplified the condition.
     */
    public function testTheKeyIsNotComparedWithAnOperator(): void
    {
        $this->assertSame(
            0,
            preg_match('/\$key\s*[!=]=+\s*\$requestKey|\$requestKey\s*[!=]=+\s*\$key/', $this->source),
            'the webhook key is being compared with an operator instead of hash_equals()'
        );
    }

    /**
     * `wkey` comes off the query string, so a caller can make it an array, and
     * hash_equals() raises a TypeError on anything that is not a string. The
     * guard has to be evaluated before it, in the same condition.
     */
    public function testANonStringKeyIsRefusedBeforeItReachesHashEquals(): void
    {
        $guard = strpos($this->source, 'is_string($requestKey)');
        $this->assertNotFalse($guard, 'no is_string() guard on the request key');

        $compare = strpos($this->source, 'hash_equals($key, $requestKey)');
        $this->assertNotFalse($compare);
        $this->assertLessThan(
            $compare,
            $guard,
            'the is_string() guard must be evaluated before hash_equals()'
        );
    }
}
