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

namespace Ebizmarts\MailChimp\Model;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The QoS service status of a store view's API key.
 *
 * The status lives in mailchimp-lib, which verifies it, stores it and honours
 * it: while it pauses the key, every API call raises before it leaves. This
 * class only reads it, so the admin can say why and the module can skip work
 * that would fail anyway instead of logging the same error every minute.
 *
 * It reads the status for the API key directly rather than through getApi():
 * the admin banner asks on every admin page, and getApi() points the shared
 * client at a store as a side effect.
 *
 * Guarded on the library: composer can pair this module with a library that
 * has no service status (before 3.0.54). Then there is nothing to read and
 * nothing is paused. Both class names are accepted, so the order in which the
 * library and the module are updated does not matter.
 */
class ServiceStatus
{
    /** The class name from 3.0.54 on. */
    const READER_CLASS = 'Mailchimp_ServiceStatus';

    /** The class name the same reader had before 3.0.54 was released. */
    const LEGACY_READER_CLASS = 'Mailchimp_Hold';

    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var object|null|false null until first use, false when the library has none
     */
    private $reader = null;

    /**
     * @var array install id => status array or null, for this process
     */
    private $statuses = [];

    /**
     * @param MailChimpHelper       $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(MailChimpHelper $helper, StoreManagerInterface $storeManager)
    {
        $this->helper       = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * The service status of this store view's API key, or null for none.
     *
     * Returned while it only warns too (`blocking` false, `blocks_from` ahead).
     *
     * @param  int|string|null $storeId
     * @return array|null code, reason, ref, iat, exp, blocks_from, blocking, since
     */
    public function forStore($storeId)
    {
        try {
            $apiKey = (string)$this->helper->getApiKey($storeId);
        } catch (\Throwable $e) {
            return null;
        }

        return $this->forApiKey($apiKey);
    }

    /**
     * Whether this store view's calls are paused right now.
     *
     * @param  int|string|null $storeId
     * @return bool
     */
    public function isPaused($storeId)
    {
        $status = $this->forStore($storeId);

        return $status !== null && !empty($status['blocking']);
    }

    /**
     * Every status on the store views that sync, one entry per API key.
     *
     * @return array[] each: status, stores (store view names)
     */
    public function active()
    {
        $found = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            try {
                if (!$this->helper->isMailChimpEnabled($storeId)) {
                    continue;
                }
                $apiKey = (string)$this->helper->getApiKey($storeId);
            } catch (\Throwable $e) {
                continue;
            }
            $status = $this->forApiKey($apiKey);
            if ($status === null) {
                continue;
            }
            $id = \Mailchimp_Telemetry::installId($apiKey);
            if (!isset($found[$id])) {
                $found[$id] = ['status' => $status, 'stores' => []];
            }
            $found[$id]['stores'][] = (string)$store->getName();
        }

        return array_values($found);
    }

    /**
     * The service status report the QoS service asks every client for, for
     * this store view's API key: what the library has stored right now.
     *
     * Empty when the library cannot honour a status at all (none, or no
     * sodium): absent fields tell the service this client does not report,
     * which is the truth, where `none` would claim it checked.
     *
     * @param  int|string|null $storeId
     * @return array svc_state, svc_ref, svc_code, svc_since; or []
     */
    public function report($storeId)
    {
        $reader = $this->reader();
        if ($reader === false) {
            return [];
        }
        try {
            $apiKey = (string)$this->helper->getApiKey($storeId);
        } catch (\Throwable $e) {
            return [];
        }
        if ($apiKey === '') {
            return [];
        }

        $status = $this->forApiKey($apiKey);
        if ($status === null) {
            return ['svc_state' => 'none', 'svc_ref' => null, 'svc_code' => null, 'svc_since' => null];
        }

        $since = isset($status['since']) ? (int)$status['since'] : (isset($status['iat']) ? (int)$status['iat'] : 0);

        return [
            'svc_state' => !empty($status['blocking']) ? 'held' : 'warning',
            'svc_ref'   => isset($status['ref']) ? (string)$status['ref'] : null,
            'svc_code'  => isset($status['code']) ? (string)$status['code'] : null,
            'svc_since' => $since > 0 ? $since : null,
        ];
    }

    /**
     * Forget what this process read, after a control was handed to the
     * library: the next question reads the library's file again.
     *
     * @return void
     */
    public function reset()
    {
        $this->statuses = [];
        if ($this->reader !== false) {
            $this->reader = null;
        }
    }

    /**
     * @param  string $apiKey
     * @return array|null
     */
    private function forApiKey($apiKey)
    {
        if ($apiKey === '') {
            return null;
        }
        $reader = $this->reader();
        if ($reader === false) {
            return null;
        }

        try {
            $id = \Mailchimp_Telemetry::installId($apiKey);
            if (!array_key_exists($id, $this->statuses)) {
                $status = $reader->get($id);
                $this->statuses[$id] = is_array($status) ? $status : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return $this->statuses[$id];
    }

    /**
     * @return object|false
     */
    private function reader()
    {
        if ($this->reader === null) {
            $this->reader = false;
            $class = $this->readerClass();
            if ($class !== null
                && class_exists(\Mailchimp_Telemetry::class)
                && method_exists(\Mailchimp_Telemetry::class, 'installId')
                && (!method_exists($class, 'supported') || $class::supported())
            ) {
                $this->reader = $this->createReader($class);
            }
        }

        return $this->reader;
    }

    /**
     * Which of the library's two names for the reader is present, or null.
     *
     * @return string|null
     */
    public function readerClass()
    {
        foreach ($this->readerClasses() as $class) {
            if (class_exists($class) && method_exists($class, 'get')) {
                return $class;
            }
        }

        return null;
    }

    /**
     * The library's names for the reader, newest first.
     *
     * @return string[]
     */
    protected function readerClasses()
    {
        return [self::READER_CLASS, self::LEGACY_READER_CLASS];
    }

    /**
     * Separate so a test can hand in a reader with its own keys and directory.
     *
     * @param  string $class
     * @return object
     */
    protected function createReader($class)
    {
        return new $class();
    }
}
