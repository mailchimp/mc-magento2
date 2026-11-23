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

use Ebizmarts\MailChimp\Model\SyncHold as SyncHoldReader;
use Magento\Framework\Escaper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * The admin banner for a hold ebizmarts has placed on Mailchimp sync.
 *
 * A system message rather than an inbox notification: it stays at the top of
 * every admin page for as long as the hold applies, and goes away on its own
 * when it is released or expires.
 */
class SyncHold implements MessageInterface
{
    const CONTACT = 'mailchimp@help.ebizmarts.com';

    /**
     * @var SyncHoldReader
     */
    private $holds;

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
     * @param SyncHoldReader $holds
     * @param UrlInterface   $urlBuilder
     * @param Escaper        $escaper
     */
    public function __construct(SyncHoldReader $holds, UrlInterface $urlBuilder, Escaper $escaper)
    {
        $this->holds      = $holds;
        $this->urlBuilder = $urlBuilder;
        $this->escaper    = $escaper;
    }

    /**
     * Changes with the hold, so a new hold is a new message.
     *
     * @return string
     */
    public function getIdentity()
    {
        $parts = [];
        foreach ($this->active() as $entry) {
            $hold    = $entry['hold'];
            $parts[] = $hold['code'] . '|' . $hold['ref'] . '|' . $hold['iat'] . '|' . (int)$hold['blocking'];
        }

        return 'ebizmarts_mailchimp_sync_hold_' . hash('sha256', implode(',', $parts));
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
            $lines[] = $this->line($entry['hold'], $entry['stores']);
        }

        return implode('<br/>', $lines);
    }

    /**
     * Critical while calls are stopped; major while it only warns.
     *
     * @return int
     */
    public function getSeverity()
    {
        foreach ($this->active() as $entry) {
            if (!empty($entry['hold']['blocking'])) {
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
                $this->active = $this->holds->active();
            } catch (\Throwable $e) {
                $this->active = [];
            }
        }

        return $this->active;
    }

    /**
     * One sentence per hold: what happened, what to do, who to ask.
     *
     * @param  array    $hold
     * @param  string[] $stores
     * @return string
     */
    private function line(array $hold, array $stores)
    {
        $contact = $this->escaper->escapeHtml(self::CONTACT);
        $code    = isset($hold['code']) ? (string)$hold['code'] : 'BLOCK';

        switch ($code) {
            case 'DEPRECATED':
                $what = __('This Mailchimp extension has been retired.');
                $todo = __('Email %1 for directions.', $contact);
                break;
            case 'UPGRADE':
                $what = __('This Mailchimp extension needs to be updated.');
                $todo = __('Update it to the latest version, or email %1 for directions.', $contact);
                break;
            case 'NEW_TOKEN':
                $what = __('This Mailchimp account needs to be reconnected.');
                $todo = __(
                    '<a href="%1">Reconnect it</a> from the Mailchimp configuration, or email %2 for directions.',
                    $this->escaper->escapeUrl(
                        $this->urlBuilder->getUrl('adminhtml/system_config/edit', ['section' => 'mailchimp'])
                    ),
                    $contact
                );
                break;
            default:
                $what = __('Your data and settings are kept.');
                $todo = __('Email %1 for directions.', $contact);
                break;
        }

        if (empty($hold['blocking']) && !empty($hold['blocks_from'])) {
            $when = __(
                'Mailchimp sync will be paused by ebizmarts on %1 (UTC).',
                $this->escaper->escapeHtml(gmdate('Y-m-d H:i', (int)$hold['blocks_from']))
            );
        } else {
            $when = __('Mailchimp sync is paused by ebizmarts.');
        }

        $text = $when . ' ' . $what . ' ' . $todo;

        if ($stores) {
            $text .= ' ' . __('Store views: %1.', $this->escaper->escapeHtml(implode(', ', $stores)));
        }
        if (!empty($hold['ref'])) {
            $text .= ' ' . __('Reference: %1.', $this->escaper->escapeHtml((string)$hold['ref']));
        }

        return $text;
    }
}
