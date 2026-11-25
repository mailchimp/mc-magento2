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

namespace Ebizmarts\MailChimp\Model\System\Message;

use Ebizmarts\MailChimp\Model\ServiceStatus as ServiceStatusReader;
use Magento\Framework\Escaper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * The admin notice for a QoS service status that pauses Mailchimp sync.
 *
 * A system message rather than an inbox notification: it stays at the top of
 * every admin page for as long as the status applies, and goes away on its
 * own when the service resumes sync or the status expires.
 */
class ServiceStatus implements MessageInterface
{
    const CONTACT = 'mailchimp@help.ebizmarts.com';

    /**
     * @var ServiceStatusReader
     */
    private $statuses;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var array[]|null
     */
    private $active = null;

    /**
     * @param ServiceStatusReader $statuses
     * @param UrlInterface        $urlBuilder
     * @param Escaper             $escaper
     */
    public function __construct(ServiceStatusReader $statuses, UrlInterface $urlBuilder, Escaper $escaper)
    {
        $this->statuses   = $statuses;
        $this->urlBuilder = $urlBuilder;
        $this->escaper    = $escaper;
    }

    /**
     * Changes with what the notice says, so a new status is a new message.
     *
     * Not with `iat`: the service signs the same status again from time to
     * time, and a dismissed notice must not come back for that alone.
     *
     * @return string
     */
    public function getIdentity()
    {
        $parts = [];
        foreach ($this->active() as $entry) {
            $status  = $entry['status'];
            $parts[] = $status['code'] . '|' . $status['ref'] . '|' . (int)$status['blocking']
                . '|' . (int)$status['blocks_from'];
        }

        return 'ebizmarts_mailchimp_service_status_' . hash('sha256', implode(',', $parts));
    }

    /**
     * @return bool
     */
    public function isDisplayed()
    {
        return !empty($this->active());
    }

    /**
     * @return string
     */
    public function getText()
    {
        $lines = [];
        foreach ($this->active() as $entry) {
            $lines[] = $this->line($entry['status'], $entry['stores']);
        }

        return implode('<br/>', $lines);
    }

    /**
     * Critical while calls are paused; major while it only warns.
     *
     * @return int
     */
    public function getSeverity()
    {
        foreach ($this->active() as $entry) {
            if (!empty($entry['status']['blocking'])) {
                return self::SEVERITY_CRITICAL;
            }
        }

        return self::SEVERITY_MAJOR;
    }

    /**
     * @return array[]
     */
    private function active()
    {
        if ($this->active === null) {
            try {
                $this->active = $this->statuses->active();
            } catch (\Throwable $e) {
                $this->active = [];
            }
        }

        return $this->active;
    }

    /**
     * One line per status: what happens, what it means, what to do.
     *
     * @param  array    $status
     * @param  string[] $stores
     * @return string
     */
    private function line(array $status, array $stores)
    {
        $contact = $this->escaper->escapeHtml(self::CONTACT);
        $code    = isset($status['code']) ? (string)$status['code'] : 'BLOCK';
        $paused  = !(empty($status['blocking']) && !empty($status['blocks_from']));

        switch ($code) {
            case 'DEPRECATED':
                $what = __('This version is no longer supported.');
                $todo = __('Email %1.', $contact);
                break;
            case 'UPGRADE':
                $what = __('This version needs to be updated.');
                $todo = __('Update it to the latest version, or email %1.', $contact);
                break;
            case 'NEW_TOKEN':
                $what = __('This Mailchimp account needs to be reconnected.');
                $todo = __(
                    '<a href="%1">Reconnect it</a> from the Mailchimp configuration, or email %2.',
                    $this->escaper->escapeUrl(
                        $this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section' => 'mailchimp'])
                    ),
                    $contact
                );
                break;
            default:
                $what = __('Your data and settings are kept.');
                $todo = __('Email %1.', $contact);
                break;
        }

        if ($paused) {
            $when    = __('Mailchimp sync is paused by the service.');
            $changes = __(
                'While sync is paused, changes made in Magento, including unsubscribes, are not sent to Mailchimp.'
            );
        } else {
            $when    = __(
                'Mailchimp sync will be paused by the service on %1 (UTC).',
                $this->escaper->escapeHtml(gmdate('Y-m-d H:i', (int)$status['blocks_from']))
            );
            $changes = __(
                'From then on, changes made in Magento, including unsubscribes, are not sent to Mailchimp.'
            );
        }

        $text = $when . ' ' . $what . ' ' . $changes . ' ' . $todo;

        if ($stores) {
            $text .= ' ' . __('Store views: %1.', $this->escaper->escapeHtml(implode(', ', $stores)));
        }
        if (!empty($status['ref'])) {
            $text .= ' ' . __('Reference: %1.', $this->escaper->escapeHtml((string)$status['ref']));
        }

        return $text;
    }
}
