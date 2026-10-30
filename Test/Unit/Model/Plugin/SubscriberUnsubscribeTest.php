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
use Ebizmarts\MailChimp\Model\Plugin\Subscriber as Plugin;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mirroring an unsubscribe to Mailchimp.
 *
 * The hook is on the model because that is where every path ends: the emailed
 * link, the admin grid's mass action, and `SubscriptionManager::unsubscribe()`,
 * which delegates to it. It is `around` with the mirror in a `finally`, because
 * the platform saves the row and then sends its own email -- so a mail
 * transport that raises leaves the shopper unsubscribed in Magento and, with an
 * `after` plugin, still subscribed on Mailchimp's side.
 */
class SubscriberUnsubscribeTest extends TestCase
{
    /** @var MockObject */
    private $members;

    /** @var MailChimpHelper|MockObject */
    private $helper;

    protected function setUp(): void
    {
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
        $this->helper->method('getApi')->willReturn($api);
        $this->helper->method('getDefaultList')->willReturn('a1b2c3d4e5');
    }

    private function makePlugin(bool $enabled = true): Plugin
    {
        $this->helper->method('isMailChimpEnabled')->willReturn($enabled);

        return new Plugin($this->helper, $this->createMock(StoreManagerInterface::class));
    }

    /**
     * A subscriber whose three getters answer what the case needs.
     *
     * Not a PHPUnit double: `getSubscriberStatus()`, `getSubscriberEmail()` and
     * `getStoreId()` are all magic on this model -- `getStatus()` and
     * `getEmail()` are the only two declared -- and a mock cannot be told to
     * answer a method that does not exist. An explicit subclass says what it
     * stands for and does not depend on which mocking API a PHPUnit version
     * offers for magic calls.
     *
     * @return Subscriber
     */
    private function makeSubscriber(int $status, string $email = 'shopper@example.com')
    {
        return new class ($status, $email) extends Subscriber {
            /** @var int */
            private $subscriberStatus;

            /** @var string */
            private $subscriberEmail;

            // Deliberately does not call the parent constructor: the model's
            // dependencies are irrelevant to the three answers below.
            public function __construct($status, $email)
            {
                $this->subscriberStatus = $status;
                $this->subscriberEmail = $email;
            }

            public function getSubscriberStatus()
            {
                return $this->subscriberStatus;
            }

            public function getSubscriberEmail()
            {
                return $this->subscriberEmail;
            }

            public function getStoreId()
            {
                return 1;
            }
        };
    }

    public function testMirrorsAnUnsubscribeThatWentThrough(): void
    {
        $this->members->expects($this->once())
            ->method('update')
            ->with('a1b2c3d4e5', hash('md5', 'shopper@example.com'), null, 'unsubscribed');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_UNSUBSCRIBED);

        $this->assertSame(
            'lo que devolvio el proceed',
            $this->makePlugin()->aroundUnsubscribe($subscriber, function () {
                return 'lo que devolvio el proceed';
            })
        );
    }

    /**
     * The confirmation-code case: the platform raises before it touches the
     * row, so the status is still subscribed and there is nothing to mirror.
     */
    public function testSaysNothingWhenTheRowNeverTransitioned(): void
    {
        $this->members->expects($this->never())->method('update');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_SUBSCRIBED);

        $this->expectException(\RuntimeException::class);

        $this->makePlugin()->aroundUnsubscribe($subscriber, function () {
            throw new \RuntimeException('invalid subscription confirmation code');
        });
    }

    /**
     * The reason this is `around` and not `after`. The row is saved before the
     * platform sends its email, so a failing transport raises with the shopper
     * already unsubscribed -- and Mailchimp still has to hear about it.
     */
    public function testMirrorsEvenWhenTheUnsubscriptionEmailFails(): void
    {
        $this->members->expects($this->once())->method('update');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_UNSUBSCRIBED);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unable to send mail');

        $this->makePlugin()->aroundUnsubscribe($subscriber, function () {
            throw new \RuntimeException('unable to send mail');
        });
    }

    public function testSaysNothingWhenMailchimpIsDisabledForTheStore(): void
    {
        $this->members->expects($this->never())->method('update');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_UNSUBSCRIBED);

        $this->makePlugin(false)->aroundUnsubscribe($subscriber, function () {
            return null;
        });
    }

    public function testTheAddressIsLowercasedBeforeHashing(): void
    {
        $this->members->expects($this->once())
            ->method('update')
            ->with($this->anything(), hash('md5', 'shopper@example.com'));

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_UNSUBSCRIBED, 'Shopper@Example.com');

        $this->makePlugin()->aroundUnsubscribe($subscriber, function () {
            return null;
        });
    }
}
