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

namespace Ebizmarts\MailChimp\Test\Unit\Cron;

use Ebizmarts\MailChimp\Cron\Ecommerce;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use PHPUnit\Framework\TestCase;

/**
 * Which failures are allowed to conclude that a credential is dead.
 *
 * Only the account call does. It is the one request in the run that asks
 * about the credential and nothing else.
 */
class ApiKeyVerdictTest extends TestCase
{
    /**
     * The real helper keys this on a hash of the credential, not on the store,
     * so every view sharing one key shares the verdict. The double has to model
     * that or the test proves something the code does not do.
     *
     * @var bool
     */
    private $marked = false;

    /**
     * @param  mixed $raise   throwable for root->info(), or null to succeed
     * @return Ecommerce
     */
    /** @var object */
    private $apiRoot;

    private function cron($raise)
    {
        $this->marked = false;

        $api  = new \stdClass();
        $api->root = new class($raise) {
            private $raise;
            public function __construct($raise) { $this->raise = $raise; }
            public $calls = 0;
            public function info()
            {
                $this->calls++;
                if ($this->raise) { throw $this->raise; }
                return ['account_id' => 'abc'];
            }
        };

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getApi')->willReturn($api);
        $helper->method('isApiKeyFailed')->willReturnCallback(function () {
            return $this->marked;
        });
        $helper->method('markApiKeyFailed')->willReturnCallback(function () {
            $this->marked = true;
        });

        $cron = $this->getMockBuilder(Ecommerce::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $prop = new \ReflectionProperty(Ecommerce::class, '_helper');
        $prop->setAccessible(true);
        $prop->setValue($cron, $helper);

        $this->apiRoot = $api->root;

        return $cron;
    }

    /**
     * @param  Ecommerce $cron
     * @param  int       $storeId
     * @return bool
     */
    private function ping(Ecommerce $cron, $storeId)
    {
        $m = new \ReflectionMethod(Ecommerce::class, '_ping');
        $m->setAccessible(true);

        return $m->invoke($cron, $storeId);
    }

    public function testARejectedCredentialIsRecorded()
    {
        $cron = $this->cron(new \Mailchimp_Error('/', 'GET', '', 'API Key Invalid', 'your key is wrong'));

        $this->assertFalse($this->ping($cron, 1));
        $this->assertTrue($this->marked);
    }

    /**
     * The one dipola caught: DNS, TLS and timeouts arrive as Mailchimp_HttpError,
     * which extends Mailchimp_Error. Recording those would let a single network
     * blip silence every store view behind it.
     */
    public function testATransportFailureIsNotRecorded()
    {
        $cron = $this->cron(new \Mailchimp_HttpError('/', 'GET', '', '', 'Could not resolve host'));

        $this->assertFalse($this->ping($cron, 1));
        $this->assertFalse($this->marked);
    }

    public function testASuccessfulCallRecordsNothing()
    {
        $cron = $this->cron(null);

        $this->assertTrue($this->ping($cron, 1));
        $this->assertFalse($this->marked);
    }

    /**
     * Once recorded, the remaining store views cost nothing at all.
     */
    public function testAKnownFailedCredentialShortCircuits()
    {
        $cron = $this->cron(new \Mailchimp_Error('/', 'GET', '', 'API Key Invalid', 'your key is wrong'));

        $this->assertFalse($this->ping($cron, 1));
        $this->assertFalse($this->ping($cron, 2));
        $this->assertFalse($this->ping($cron, 3));
        $this->assertSame(1, $this->apiRoot->calls, 'the account was asked once, not once per view');
    }
}
