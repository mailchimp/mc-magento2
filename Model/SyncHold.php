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
 * Whether ebizmarts has paused a store view's Mailchimp calls.
 *
 * The hold itself lives in mailchimp-lib, which verifies it, stores it and
 * enforces it: while it applies, every API call raises before it leaves. This
 * class only reads it, so the admin can say why and the crons can skip work
 * that would fail anyway instead of logging the same error every minute.
 *
 * It reads the hold for the API key directly rather than through getApi():
 * the admin banner asks on every admin page, and getApi() points the shared
 * client at a store as a side effect.
 *
 * Guarded on the library: composer can pair this module with a library older
 * than the hold (3.0.54). Then there is no hold to read and nothing is paused.
 */
class SyncHold
{
    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Mailchimp_Hold|null|false null until first use, false when the library has none
     */
    private $reader = null;

    /**
     * @var array install id => hold array or null, for this process
     */
    private $holds = [];

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
     * The hold on this store view's API key, or null.
     *
     * Returned while it only warns too (`blocking` false, `blocks_from` ahead).
     *
     * @param  int|string|null $storeId
     * @return array|null code, reason, ref, iat, exp, blocks_from, blocking
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
     * Whether this store view's calls are stopped right now.
     *
     * @param  int|string|null $storeId
     * @return bool
     */
    public function isBlocking($storeId)
    {
        $hold = $this->forStore($storeId);

        return $hold !== null && !empty($hold['blocking']);
    }

    /**
     * Every hold on the store views that sync, one entry per API key.
     *
     * @return array[] each: hold, stores (store view names)
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
            $hold = $this->forApiKey($apiKey);
            if ($hold === null) {
                continue;
            }
            $id = \Mailchimp_Telemetry::installId($apiKey);
            if (!isset($found[$id])) {
                $found[$id] = ['hold' => $hold, 'stores' => []];
            }
            $found[$id]['stores'][] = (string)$store->getName();
        }

        return array_values($found);
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
            if (!array_key_exists($id, $this->holds)) {
                $hold = $reader->get($id);
                $this->holds[$id] = is_array($hold) ? $hold : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return $this->holds[$id];
    }

    /**
     * @return \Mailchimp_Hold|false
     */
    private function reader()
    {
        if ($this->reader === null) {
            $this->reader = false;
            if (class_exists(\Mailchimp_Hold::class)
                && method_exists(\Mailchimp_Hold::class, 'get')
                && class_exists(\Mailchimp_Telemetry::class)
                && method_exists(\Mailchimp_Telemetry::class, 'installId')
            ) {
                $this->reader = $this->createReader();
            }
        }

        return $this->reader;
    }

    /**
     * Separate so a test can hand in a reader with its own keys and directory.
     *
     * @return \Mailchimp_Hold
     */
    protected function createReader()
    {
        return new \Mailchimp_Hold();
    }
}
