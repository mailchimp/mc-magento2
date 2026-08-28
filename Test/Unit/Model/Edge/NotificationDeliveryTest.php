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
use Ebizmarts\MailChimp\Model\Edge\NotificationDelivery;
use Ebizmarts\MailChimp\Model\Edge\Response;
use Magento\Framework\Notification\NotifierInterface;
use PHPUnit\Framework\TestCase;

class NotificationDeliveryTest extends TestCase
{
    /**
     * The wire shape the service actually returns from the pull:
     * { notifications: [{ id, subject, message, created_at, priority }] }
     *
     * @param  string $priority
     * @return array
     */
    private function wireItem($priority = 'notice', $id = 'uid-1')
    {
        return [
            'id'         => $id,
            'subject'    => 'Scheduled maintenance',
            'message'    => 'Maintenance window this weekend.',
            'created_at' => '2026-08-24T12:00:00Z',
            'priority'   => $priority,
        ];
    }

    public function testZeroCountNeverPulls(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('pullNotifications');

        $delivery = new NotificationDelivery(
            $client,
            $this->createMock(MailChimpHelper::class),
            $this->createMock(NotifierInterface::class)
        );

        // The ping reply carries the count under `notifications`.
        $delivery->handle(1, 'token', ['ok' => true, 'notifications' => 0]);
    }

    public function testNonZeroCountPullsAndDelivers(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('pullNotifications')
            ->willReturn(new Response(Response::OK, 200, ['notifications' => [$this->wireItem()]]));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->once())
            ->method('addNotice')
            ->with('Scheduled maintenance', 'Maintenance window this weekend.', '');

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->anything(), 'uid-1', 1, $this->anything());

        (new NotificationDelivery($client, $helper, $notifier))
            ->handle(1, 'token', ['ok' => true, 'notifications' => 1]);
    }

    public function testCriticalPriorityMapsToAddCritical(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')
            ->willReturn(new Response(Response::OK, 200, ['notifications' => [$this->wireItem('critical')]]));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->once())->method('addCritical');
        $notifier->expects($this->never())->method('addNotice');

        (new NotificationDelivery($client, $this->createMock(MailChimpHelper::class), $notifier))
            ->handle(1, 'token', ['notifications' => 1]);
    }

    public function testMajorPriorityMapsToAddMajor(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')
            ->willReturn(new Response(Response::OK, 200, ['notifications' => [$this->wireItem('major')]]));

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->once())->method('addMajor');

        (new NotificationDelivery($client, $this->createMock(MailChimpHelper::class), $notifier))
            ->handle(1, 'token', ['notifications' => 1]);
    }

    public function testAFailedPullConfirmsNothing(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(new Response(Response::FAILED, 503));

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->never())->method('saveConfigValue');

        (new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)))
            ->handle(1, 'token', ['notifications' => 3]);
    }

    /**
     * A message we could not write must not be reported as delivered — that is
     * the whole reason the ack exists.
     */
    public function testAMessageThatCannotBeWrittenIsNotConfirmed(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$this->wireItem('notice', 'uid-boom')]])
        );

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->method('addNotice')->willThrowException(new \Exception('inbox unavailable'));

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->never())->method('saveConfigValue');

        (new NotificationDelivery($client, $helper, $notifier))
            ->handle(1, 'token', ['notifications' => 1]);
    }

    /**
     * A batch still waiting to be acknowledged must not be dropped by a newer
     * one, or those messages stay unconfirmed forever.
     */
    public function testDeliveredUidsAppendToWhatIsStillPending(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$this->wireItem('notice', 'uid-2')]])
        );

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->anything(), 'uid-0,uid-1,uid-2', 1, $this->anything());

        (new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)))
            ->handle(1, 'token', ['notifications' => 1], ['uid-0', 'uid-1']);
    }

    /**
     * What is still pending comes from the caller, never from configuration.
     *
     * The caller clears that value immediately before calling in, and reading
     * it back here would not reliably see the clear: within one process a scope
     * already loaded is refreshed only as a side effect of the cache-warming
     * path, which is skipped when the config cache type is disabled. A read
     * would return the pre-clear list and re-send uids already acknowledged on
     * every tick, forever.
     */
    public function testWhatIsStillPendingIsNeverReadBackFromConfig(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$this->wireItem('notice', 'uid-2')]])
        );

        $helper = $this->createMock(MailChimpHelper::class);
        // A stale read would hand back a list the caller has just cleared.
        $helper->method('getConfigValue')->willReturn('uid-0,uid-1');
        $helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->anything(), 'uid-2', 1, $this->anything());

        (new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)))
            ->handle(1, 'token', ['notifications' => 1], []);
    }

    /**
     * A message with no subject is never written to the inbox, so it must not
     * be acknowledged either — that would tell the service somebody saw it.
     */
    public function testAnEmptySubjectIsNeitherDeliveredNorConfirmed(): void
    {
        $empty            = $this->wireItem('notice', 'uid-empty');
        $empty['subject'] = '';

        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$empty]])
        );

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->never())->method('addNotice');
        $notifier->expects($this->never())->method('addMajor');
        $notifier->expects($this->never())->method('addCritical');

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->never())->method('saveConfigValue');

        (new NotificationDelivery($client, $helper, $notifier))
            ->handle(1, 'token', ['notifications' => 1], []);
    }

    /**
     * An unwritable message in the middle of a batch must not take down the
     * one before it, nor confirm the one after.
     */
    public function testAnEmptySubjectDoesNotStopTheRestOfTheBatch(): void
    {
        $empty            = $this->wireItem('notice', 'uid-empty');
        $empty['subject'] = '';

        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(
                Response::OK,
                200,
                ['notifications' => [$this->wireItem('notice', 'uid-a'), $empty, $this->wireItem('major', 'uid-b')]]
            )
        );

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->anything(), 'uid-a,uid-b', 1, $this->anything());

        (new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)))
            ->handle(1, 'token', ['notifications' => 3], []);
    }

    public function testEveryDeliveredUidIsQueuedNotJustTheLast(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(
                Response::OK,
                200,
                ['notifications' => [$this->wireItem('notice', 'uid-a'), $this->wireItem('major', 'uid-b')]]
            )
        );

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->expects($this->once())
            ->method('saveConfigValue')
            ->with($this->anything(), 'uid-a,uid-b', 1, $this->anything());

        (new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class)))
            ->handle(1, 'token', ['notifications' => 2], []);
    }

    /**
     * The service addresses store views, so one message arrives once per
     * registered view of the same install. Magento's inbox is install-wide,
     * so writing every arrival would put the same message in the bell N times.
     */
    public function testTheSameMessageIsWrittenToTheInboxOnlyOnce(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$this->wireItem('notice', 'uid-broadcast')]])
        );

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->once())->method('addNotice');

        $delivery = new NotificationDelivery($client, $this->createMock(MailChimpHelper::class), $notifier);

        // Five store views of one install, one cron process, same message.
        foreach ([1, 2, 3, 4, 5] as $storeId) {
            $delivery->handle($storeId, 'token', ['notifications' => 1], []);
        }
    }

    /**
     * A suppressed duplicate must still be acknowledged. It did reach the
     * inbox once, and the service tracks delivery per store view — a view that
     * never confirms is offered the same message forever.
     */
    public function testASuppressedDuplicateIsStillAcknowledgedByEveryStoreView(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(Response::OK, 200, ['notifications' => [$this->wireItem('notice', 'uid-broadcast')]])
        );

        $acked = [];
        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('saveConfigValue')->willReturnCallback(
            function ($path, $value, $storeId) use (&$acked) {
                $acked[] = $storeId;
            }
        );

        $delivery = new NotificationDelivery($client, $helper, $this->createMock(NotifierInterface::class));

        foreach ([1, 2, 3] as $storeId) {
            $delivery->handle($storeId, 'token', ['notifications' => 1], []);
        }

        $this->assertSame([1, 2, 3], $acked, 'every store view has to confirm its own copy');
    }

    /**
     * Suppression is by uid, not by content — two genuinely different messages
     * must both reach the merchant even in the same run.
     */
    public function testDifferentMessagesAreNotSuppressed(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('pullNotifications')->willReturn(
            new Response(
                Response::OK,
                200,
                ['notifications' => [$this->wireItem('notice', 'uid-a'), $this->wireItem('notice', 'uid-b')]]
            )
        );

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects($this->exactly(2))->method('addNotice');

        (new NotificationDelivery($client, $this->createMock(MailChimpHelper::class), $notifier))
            ->handle(1, 'token', ['notifications' => 2], []);
    }

}
