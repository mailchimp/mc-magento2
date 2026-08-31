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

namespace Ebizmarts\MailChimp\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\HTTP\Client\CurlFactory;

/**
 * HTTP client for the ebizmarts reporting service.
 *
 * The base URL is a constant rather than config on purpose: it is not a
 * merchant setting and nothing should be able to point a store's beacon
 * somewhere else through the admin.
 */
class Client
{
    const BASE_URL           = 'https://apps.ebizmarts.com/mc4magento/v1';
    const PATH_REGISTER      = '/legacy/register';
    const PATH_PING          = '/legacy/ping';
    const PATH_NOTIFICATIONS = '/notifications';

    /**
     * A beacon runs inside a cron tick that iterates every store view, so a
     * hung service must not stall every installation. Well below the default 300.
     */
    const TIMEOUT = 15;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @param CurlFactory     $curlFactory
     * @param MailChimpHelper $helper
     */
    public function __construct(
        CurlFactory $curlFactory,
        MailChimpHelper $helper
    ) {
        $this->curlFactory = $curlFactory;
        $this->helper      = $helper;
    }

    /**
     * Register a store view. No authentication; the service returns the token.
     *
     * Idempotent upsert on the service, so retrying is safe.
     *
     * @param  array $body
     * @return Response
     */
    public function register(array $body)
    {
        return $this->send(self::PATH_REGISTER, $body, null);
    }

    /**
     * Send the status report.
     *
     * @param  string $token
     * @param  array  $body
     * @return Response
     */
    public function ping($token, array $body)
    {
        return $this->send(self::PATH_PING, $body, $token);
    }

    /**
     * Pull pending notifications. Only called when the ping reported a count.
     *
     * @param  string $token
     * @return Response
     */
    public function pullNotifications($token)
    {
        return $this->send(self::PATH_NOTIFICATIONS, [], $token);
    }

    /**
     * Perform the request and classify the outcome.
     *
     * A transport failure (DNS, timeout, reset) surfaces from the curl client
     * as an exception; it is classified as FAILED so the caller leaves the
     * token untouched and simply tries again next hour.
     *
     * @param  string      $path
     * @param  array       $body
     * @param  string|null $token
     * @return Response
     */
    private function send($path, array $body, $token = null)
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== null && $token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $curl = $this->curlFactory->create();
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->setTimeout(self::TIMEOUT);
        $curl->setHeaders($headers);

        try {
            $curl->post(self::BASE_URL . $path, json_encode($body));
        } catch (\Exception $e) {
            $this->helper->log('Edge beacon transport failure on ' . $path . ': ' . $e->getMessage());
            return new Response(Response::FAILED);
        } catch (\Throwable $t) {
            // The point of this catch is to turn a transport problem into a
            // failed response, not to end the run. An Error escaping it would
            // skip the whole store view instead, and lose the log line saying
            // why.
            $this->helper->log('Edge beacon transport failure on ' . $path . ': ' . $t->getMessage());
            return new Response(Response::FAILED);
        }

        $status = (int)$curl->getStatus();

        if ($status === 401) {
            return new Response(Response::UNAUTHORIZED, $status);
        }

        if ($status === 429) {
            $retryAfter = $this->readRetryAfter($curl->getHeaders());
            $this->helper->log('Edge beacon rate limited on ' . $path . ', Retry-After: ' . ($retryAfter ?? 'absent'));
            return new Response(Response::RATE_LIMITED, $status, [], $retryAfter);
        }

        if ($status < 200 || $status > 299) {
            $this->helper->log('Edge beacon got HTTP ' . $status . ' on ' . $path);
            return new Response(Response::FAILED, $status);
        }

        $data = json_decode((string)$curl->getBody(), true);
        if (!is_array($data)) {
            $this->helper->log('Edge beacon got a malformed body on ' . $path);
            return new Response(Response::FAILED, $status);
        }

        return new Response(Response::OK, $status, $data);
    }

    /**
     * Read Retry-After regardless of the casing the service used.
     *
     * @param  array $headers
     * @return int|null
     */
    private function readRetryAfter(array $headers)
    {
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'retry-after') {
                return (int)$value;
            }
        }

        return null;
    }
}
