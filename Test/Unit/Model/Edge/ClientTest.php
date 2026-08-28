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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Edge\Client;
use Ebizmarts\MailChimp\Model\HTTP\Client\Curl;
use Ebizmarts\MailChimp\Model\HTTP\Client\CurlFactory;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @param  int    $status
     * @param  string $body
     * @param  array  $headers
     * @param  bool   $throw
     * @return Client
     */
    private function makeClient($status, $body = '{}', array $headers = [], $throw = false)
    {
        $curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setOption', 'setTimeout', 'setHeaders', 'post', 'getStatus', 'getBody', 'getHeaders'])
            ->getMock();

        if ($throw) {
            $curl->method('post')->willThrowException(new \Exception('Could not resolve host'));
        }

        $curl->method('getStatus')->willReturn($status);
        $curl->method('getBody')->willReturn($body);
        $curl->method('getHeaders')->willReturn($headers);

        $factory = $this->getMockBuilder(CurlFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturn($curl);

        $helper = $this->createMock(MailChimpHelper::class);

        return new Client($factory, $helper);
    }

    public function testSuccessReturnsDecodedBody(): void
    {
        $client   = $this->makeClient(201, '{"token":"ebz_abc","expires_at":123}');
        $response = $client->register(['store_url' => 'https://example.com/']);

        $this->assertTrue($response->isOk());
        $this->assertSame('ebz_abc', $response->getData()['token']);
    }

    public function testUnauthorizedIsItsOwnOutcome(): void
    {
        $response = $this->makeClient(401, '{"error":"expired","action":"reregister"}')->ping('t', []);

        $this->assertTrue($response->isUnauthorized());
        $this->assertFalse($response->isOk());
    }

    public function testRateLimitedReadsRetryAfter(): void
    {
        $response = $this->makeClient(429, '{}', ['Retry-After' => '90'])->ping('t', []);

        $this->assertTrue($response->isRateLimited());
        $this->assertSame(90, $response->getRetryAfter());
    }

    public function testRetryAfterIsCaseInsensitive(): void
    {
        $response = $this->makeClient(429, '{}', ['retry-after' => '30'])->ping('t', []);

        $this->assertSame(30, $response->getRetryAfter());
    }

    /**
     * A 5xx must never look like an auth failure: if it did, one bad deploy on
     * the service would make every installation drop its tokens at once.
     */
    public function testServerErrorIsNotUnauthorized(): void
    {
        $response = $this->makeClient(503, 'upstream down')->ping('t', []);

        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isUnauthorized());
        $this->assertFalse($response->isRateLimited());
    }

    public function testTransportFailureIsNotUnauthorized(): void
    {
        $response = $this->makeClient(0, '', [], true)->ping('t', []);

        $this->assertFalse($response->isOk());
        $this->assertFalse($response->isUnauthorized());
        $this->assertSame(0, $response->getStatus());
    }

    public function testMalformedBodyIsAFailure(): void
    {
        $response = $this->makeClient(200, 'not json at all')->ping('t', []);

        $this->assertFalse($response->isOk());
    }
}
