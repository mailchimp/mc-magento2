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
     * @return void
     */
    public function handle($storeId, $token, array $pingData)
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

            try {
                $this->deliver($item);
            } catch (\Exception $e) {
                // A message we could not write must not be confirmed, so stop
                // here and let the unconfirmed uid tell the service the truth.
                $this->helper->log('Edge notification could not be delivered: ' . $e->getMessage());
                break;
            }

            // `id` IS the opaque delivery_uid on the wire.
            if (isset($item['id']) && $item['id'] !== '') {
                $delivered[] = (string)$item['id'];
            }
        }

        if (!empty($delivered)) {
            $this->rememberForAck($storeId, $delivered);
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
     * @param  int   $storeId
     * @param  array $delivered
     * @return void
     */
    private function rememberForAck($storeId, array $delivered)
    {
        $pending = $this->helper->getConfigValue(MailChimpHelper::XML_EDGE_DELIVERY_UID, $storeId);
        $pending = $pending === null || $pending === '' ? [] : explode(',', (string)$pending);

        $merged = array_values(array_unique(array_merge($pending, $delivered)));
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
     * Hand one notification to Magento, mapped from the service's priority.
     *
     * @param  array $item
     * @return void
     */
    private function deliver(array $item)
    {
        // Wire shape is subject/message, not title/body.
        $title       = isset($item['subject']) ? (string)$item['subject'] : '';
        $description = isset($item['message']) ? (string)$item['message'] : '';
        $url         = isset($item['url']) ? (string)$item['url'] : '';

        if ($title === '') {
            return;
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
    }
}
