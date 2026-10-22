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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Plugin;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Plugin\SubscriptionManager as Plugin;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManager as Subject;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The unsubscribe mirror runs after the platform's own method, so that it
 * mirrors an unsubscribe that happened rather than one that was attempted.
 *
 * Magento returns early from `unsubscribe()` when the address has no
 * subscriber row, and raises before returning when a confirmation code does
 * not match. Writing to Mailchimp ahead of either meant telling it a shopper
 * had unsubscribed when nothing in Magento had changed — and against an
 * address Mailchimp does not hold, that write is a PATCH answered 404 and
 * swallowed, once per attempt.
 */
class SubscriptionManagerTest extends TestCase
{
    /** @var MockObject */
    private $members;

    /** @var MailChimpHelper|MockObject */
    private $helper;

    protected function setUp(): void
    {
        // $api->lists->members->update() is a chain of public properties on the
        // library's own objects, so the doubles are wired the same way the
        // library wires the real ones.
        $this->members = $this->getMockBuilder(\Mailchimp_ListsMembers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lists = $this->getMockBuilder(\Mailchimp_Lists::class)
            ->disableOriginalConstructor()
            ->getMock();
        $lists->members = $this->members;

        $api = $this->getMockBuilder(\Mailchimp::class)
            ->disableOriginalConstructor()
            ->getMock();
        $api->lists = $lists;

        $this->helper = $this->createMock(MailChimpHelper::class);
        $this->helper->method('isMailChimpEnabled')->willReturn(true);
        $this->helper->method('getApi')->willReturn($api);
        $this->helper->method('getDefaultList')->willReturn('a1b2c3d4e5');
    }

    private function makePlugin(): Plugin
    {
        return new Plugin(
            $this->helper,
            $this->createMock(CustomerRepository::class),
            $this->createMock(CustomerSession::class),
            $this->createMock(StoreManagerInterface::class),
            $this->createMock(SubscriberFactory::class)
        );
    }

    /**
     * @param  int|null $id
     * @param  int      $status
     * @return Subscriber|MockObject
     */
    private function makeSubscriber($id, int $status)
    {
        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->method('getId')->willReturn($id);
        $subscriber->method('getStatus')->willReturn($status);

        return $subscriber;
    }

    public function testMirrorsAnUnsubscribeMagentoActuallyMade(): void
    {
        $this->members->expects($this->once())
            ->method('update')
            ->with('a1b2c3d4e5', hash('md5', 'shopper@example.com'), null, 'unsubscribed');

        $result = $this->makeSubscriber(7, Subscriber::STATUS_UNSUBSCRIBED);

        $this->assertSame(
            $result,
            $this->makePlugin()->afterUnsubscribe(
                $this->createMock(Subject::class),
                $result,
                'shopper@example.com',
                1,
                'code'
            )
        );
    }

    /**
     * The 404 class: Magento has no row for the address, returns the empty
     * subscriber without unsubscribing anything, and Mailchimp must not hear
     * about it.
     */
    public function testSaysNothingWhenMagentoHasNoSubscriberRow(): void
    {
        $this->members->expects($this->never())->method('update');

        $result = $this->makeSubscriber(null, Subscriber::STATUS_NOT_ACTIVE);

        $this->makePlugin()->afterUnsubscribe(
            $this->createMock(Subject::class),
            $result,
            'stranger@example.com',
            1,
            'code'
        );
    }

    /**
     * A row that exists but did not transition — nothing was unsubscribed, so
     * there is nothing to mirror.
     */
    public function testSaysNothingWhenTheRowDidNotTransition(): void
    {
        $this->members->expects($this->never())->method('update');

        $result = $this->makeSubscriber(7, Subscriber::STATUS_SUBSCRIBED);

        $this->makePlugin()->afterUnsubscribe(
            $this->createMock(Subject::class),
            $result,
            'shopper@example.com',
            1,
            'code'
        );
    }

    public function testEmailIsLowercasedBeforeHashing(): void
    {
        $this->members->expects($this->once())
            ->method('update')
            ->with($this->anything(), hash('md5', 'shopper@example.com'));

        $result = $this->makeSubscriber(7, Subscriber::STATUS_UNSUBSCRIBED);

        $this->makePlugin()->afterUnsubscribe(
            $this->createMock(Subject::class),
            $result,
            'Shopper@Example.com',
            1,
            'code'
        );
    }
}
