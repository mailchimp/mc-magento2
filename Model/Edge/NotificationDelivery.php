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
use Magento\Framework\Notification\NotifierInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Writes edge notifications into Magento's own admin inbox.
 *
 * No new table, no UI component, no template: persistence, read state and
 * dismissal are Magento's, and the merchant sees the message in the admin bell
 * where they already look.
 *
 * Injecting NotifierInterface is safe even when Magento_AdminNotification is
 * disabled. The preference lives in app/etc/di.xml and points at the
 * framework's NotifierPool, which simply iterates a list of notifiers; with the
 * module off the list is empty and every call is a no-op rather than a fatal.
 */
class NotificationDelivery
{
    /**
     * Ceiling on the pending-ack list. Notifications are rare, so this only
     * ever bites on a store that delivered and then could not ping for a long
     * time; keeping the newest is the useful half.
     */
    const MAX_PENDING_ACKS = 50;


    /**
     * @var Client
     */
    private $client;

    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var array uids already written to the inbox by this process
     */
    private $written = [];

    /**
     * @param Client            $client
     * @param MailChimpHelper   $helper
     * @param NotifierInterface $notifier
     */
    public function __construct(
        Client $client,
        MailChimpHelper $helper,
        NotifierInterface $notifier
    ) {
        $this->client   = $client;
        $this->helper   = $helper;
        $this->notifier = $notifier;
    }

    /**
     * Act on the ping reply.
     *
     * The reply carries a count, not content. Zero, which is nearly every
     * hour, costs nothing.
     *
     * @param  int    $storeId
     * @param  string $token
     * @param  array  $pingData
     * @param  array  $stillPending uids the caller has NOT just acknowledged
     * @return void
     */
    public function handle($storeId, $token, array $pingData, array $stillPending = [])
    {
        if ($this->pendingCount($pingData) < 1) {
            return;
        }

        $response = $this->client->pullNotifications($token);
        if (!$response->isOk()) {
            return;
        }

        $items = $this->extractItems($response->getData());
        if (empty($items)) {
            return;
        }

        $delivered = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $uid = isset($item['id']) ? (string)$item['id'] : '';

            try {
                $written = $this->deliverOnce($uid, $item);
            } catch (\Exception $e) {
                // A message we could not write must not be confirmed, so stop
                // here and let the unconfirmed uid tell the service the truth.
                $this->helper->log('Edge notification could not be delivered: ' . $e->getMessage());
                break;
            } catch (\Throwable $t) {
                // Same rule for an Error. Without this it escapes the loop, and
                // the uids already delivered in this batch are lost with it
                // rather than being queued for acknowledgement.
                $this->helper->log('Edge notification could not be delivered: ' . $t->getMessage());
                break;
            }

            // Only what actually reached the inbox may be acknowledged. A
            // message with no subject is never written, and confirming it
            // would tell the service it was seen when nobody saw it.
            //
            // A message suppressed as a duplicate IS acknowledged, and must be:
            // it did reach the inbox, once. The service tracks delivery per
            // store view, so every view has to confirm its own copy or it will
            // keep offering the same message forever.
            if (!$written) {
                continue;
            }

            // `id` IS the opaque delivery_uid on the wire.
            if ($uid !== '') {
                $delivered[] = $uid;
            }
        }

        if (!empty($delivered)) {
            $this->rememberForAck($storeId, $delivered, $stillPending);
        }
    }

    /**
     * Queue delivered uids for the next ping's `received[]`.
     *
     * Appends rather than overwrites: a previous batch may still be waiting to
     * be acknowledged, and dropping it would report those messages as
     * unconfirmed forever.
     *
     * Written only when a notification actually landed in the inbox, which is
     * rare, so the config cache flush this causes is rare too.
     *
     * What is still pending arrives as an argument rather than being read back
     * from configuration. The caller clears that value just before calling in,
     * and a read here would not reliably see the clear: within one process the
     * config a scope has already loaded is only refreshed as a side effect of
     * the cache-warming path, which is skipped entirely when the config cache
     * type is disabled. Reading it back there returns the pre-clear list, and
     * the uids just acknowledged would be merged in and sent again on every
     * tick. Taking it as an argument makes the behaviour the same either way.
     *
     * @param  int   $storeId
     * @param  array $delivered
     * @param  array $stillPending uids the caller has not just acknowledged
     * @return void
     */
    private function rememberForAck($storeId, array $delivered, array $stillPending = [])
    {
        $merged = array_values(array_unique(array_merge($stillPending, $delivered)));
        if (count($merged) > self::MAX_PENDING_ACKS) {
            $merged = array_slice($merged, -self::MAX_PENDING_ACKS);
        }

        $this->helper->saveConfigValue(
            MailChimpHelper::XML_EDGE_DELIVERY_UID,
            implode(',', $merged),
            $storeId,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * How many notifications the service says are waiting.
     *
     * @param  array $pingData
     * @return int
     */
    private function pendingCount(array $pingData)
    {
        // The ping reply carries the count as a bare number under
        // `notifications`: json({ ok: true, notifications: row.pending_notif ?? 0 }).
        // Note the pull uses the SAME key for the ARRAY of messages, so the
        // type check is what keeps the two apart.
        if (isset($pingData['notifications']) && is_numeric($pingData['notifications'])) {
            return (int)$pingData['notifications'];
        }

        return 0;
    }

    /**
     * The notification list out of the pull response.
     *
     * @param  array $data
     * @return array
     */
    private function extractItems(array $data)
    {
        // The pull returns { notifications: [{ id, subject, message, created_at, priority }] }.
        if (isset($data['notifications']) && is_array($data['notifications'])) {
            return $data['notifications'];
        }

        return [];
    }

    /**
     * Write a notification to the inbox unless this process already has.
     *
     * The service addresses store views, not installations — every target but
     * a single-view one fans out, so one message arrives once per registered
     * view of the same install. Magento's inbox is install-wide and has no
     * scope column, so writing each arrival would put the same message in the
     * merchant's bell N times.
     *
     * Deduplication is per process, which covers the normal case exactly: one
     * cron run walks every store view. It does not cover views handled by
     * separate processes.
     *
     * Checking the inbox itself instead would cover that, but it cannot be done
     * through NotifierInterface. Inbox::add() always sets an `internal` key, and
     * Inbox::parse() skips its duplicate lookup whenever that key is set — so
     * the notifier never deduplicates. Passing null to reach the other branch
     * hits `Unknown column 'internal'`, because parse() only unsets the key on
     * the branch it did not take. Querying the table directly would work, but
     * would cost the property that made NotifierInterface the right choice
     * here: it degrades to doing nothing when Magento_AdminNotification is
     * disabled, rather than fataling.
     *
     * @param  string $uid  delivery uid, stable across the views of one message
     * @param  array  $item
     * @return bool   whether the merchant now has this message
     */
    private function deliverOnce($uid, array $item)
    {
        if ($uid !== '' && isset($this->written[$uid])) {
            return true;
        }

        if (!$this->deliver($item)) {
            return false;
        }

        if ($uid !== '') {
            $this->written[$uid] = true;
        }

        return true;
    }

    /**
     * Hand one notification to Magento, mapped from the service's priority.
     *
     * @param  array $item
     * @return bool  whether it was written to the inbox
     */
    private function deliver(array $item)
    {
        // Wire shape is subject/message, not title/body.
        $title       = isset($item['subject']) ? (string)$item['subject'] : '';
        $description = isset($item['message']) ? (string)$item['message'] : '';
        $url         = isset($item['url']) ? (string)$item['url'] : '';

        if ($title === '') {
            return false;
        }

        $priority = isset($item['priority']) ? strtolower((string)$item['priority']) : '';

        switch ($priority) {
            case 'critical':
                $this->notifier->addCritical($title, $description, $url);
                break;
            case 'major':
                $this->notifier->addMajor($title, $description, $url);
                break;
            default:
                $this->notifier->addNotice($title, $description, $url);
                break;
        }

        return true;
    }
}
