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

/**
 * Outcome of a single call to the reporting service.
 *
 * The outcome is deliberately coarser than the HTTP status, because only three
 * distinctions change what the caller does: the token is dead (401), we were
 * asked to back off (429), or something else went wrong and the token must be
 * left exactly as it is.
 */
class Response
{
    const OK           = 'ok';
    const UNAUTHORIZED = 'unauthorized';
    const RATE_LIMITED = 'rate_limited';
    const FAILED       = 'failed';

    /**
     * @var string
     */
    private $outcome;

    /**
     * @var int
     */
    private $status;

    /**
     * @var array
     */
    private $data;

    /**
     * @var int|null
     */
    private $retryAfter;

    /**
     * @param string   $outcome
     * @param int      $status
     * @param array    $data
     * @param int|null $retryAfter
     */
    public function __construct($outcome, $status = 0, array $data = [], $retryAfter = null)
    {
        $this->outcome    = $outcome;
        $this->status     = (int)$status;
        $this->data       = $data;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Whether the call succeeded.
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->outcome === self::OK;
    }

    /**
     * Whether the stored token must be discarded.
     *
     * @return bool
     */
    public function isUnauthorized()
    {
        return $this->outcome === self::UNAUTHORIZED;
    }

    /**
     * Whether the service asked us to back off.
     *
     * @return bool
     */
    public function isRateLimited()
    {
        return $this->outcome === self::RATE_LIMITED;
    }

    /**
     * Decoded response body.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * HTTP status, 0 when the request never completed.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Retry-After in seconds when the service sent one.
     *
     * @return int|null
     */
    public function getRetryAfter()
    {
        return $this->retryAfter;
    }
}
