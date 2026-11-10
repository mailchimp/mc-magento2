<?php

namespace Ebizmarts\MailChimp\Test\Unit\Controller\Checkout;

use Ebizmarts\MailChimp\Controller\Checkout\Success;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroup;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup as InterestGroupResource;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuccessTest extends TestCase
{
    private const BASE_URL = 'https://shop.example/';
    private const EMAIL = 'shopper@example.com';
    private const STORE_ID = 1;

    private $helper;

    private $checkoutSession;

    private $subscriberFactory;

    private $interestGroupFactory;

    private $syncHelper;

    private $redirect;

    private $response;

    private $messageManager;

    private $requestParams = [];

    private $mailchimpEnabled = true;

    private $interestInSuccess = '1';

    protected function setUp(): void
    {
        $this->helper = $this->createMock(MailChimpHelper::class);
        $this->helper->method('getBaserUrl')->willReturn(self::BASE_URL);
        $this->helper->method('isMailChimpEnabled')->willReturnCallback(function () {
            return $this->mailchimpEnabled;
        });
        $this->helper->method('getConfigValue')->willReturnCallback(function ($path) {
            return $path === MailChimpHelper::XML_INTEREST_IN_SUCCESS ? $this->interestInSuccess : null;
        });
        $this->helper->method('serialize')->willReturnCallback(function ($data) {
            return json_encode($data);
        });
        $this->helper->method('getGmtDate')->willReturn('2026-11-09 12:00:00');
        $this->helper->method('getGeneralList')->willReturn('a1b2c3d4e5');

        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->subscriberFactory = $this->createMock(SubscriberFactory::class);
        $this->interestGroupFactory = $this->createMock(MailChimpInterestGroupFactory::class);
        $this->syncHelper = $this->createMock(SyncHelper::class);
        $this->redirect = $this->createMock(RedirectInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
    }

    private function makeController(): Success
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParams')->willReturnCallback(function () {
            return $this->requestParams;
        });
        $request->method('getParam')->willReturnCallback(function ($key, $default = null) {
            return $this->requestParams[$key] ?? $default;
        });

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResponse')->willReturn($this->response);
        $context->method('getRedirect')->willReturn($this->redirect);
        $context->method('getMessageManager')->willReturn($this->messageManager);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Success(
            $context,
            $this->createMock(PageFactory::class),
            $this->helper,
            $this->checkoutSession,
            $this->subscriberFactory,
            $this->interestGroupFactory,
            $this->syncHelper,
            $storeManager
        );
    }

    private function givenTheLastOrder($orderId): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($orderId);
        $order->method('getStoreId')->willReturn($orderId ? self::STORE_ID : null);
        $order->method('getCustomerEmail')->willReturn($orderId ? self::EMAIL : null);

        $this->checkoutSession->method('getLastRealOrder')->willReturn($order);
    }

    private function makeSubscriber(array $data): MockObject
    {
        $subscriber = $this->getMockBuilder(Subscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadBySubscriberEmail', 'subscribe'])
            ->getMock();
        $subscriber->setData($data);

        return $subscriber;
    }

    private function storedSubscriberData(int $status): array
    {
        return [
            'subscriber_id' => 7,
            'subscriber_email' => self::EMAIL,
            'subscriber_status' => $status,
            'store_id' => self::STORE_ID,
        ];
    }

    private function makeInterestGroup(): MockObject
    {
        $resource = $this->createMock(InterestGroupResource::class);
        $resource->expects($this->once())->method('save');

        $interestGroup = $this->getMockBuilder(MailChimpInterestGroup::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBySubscriberIdStoreId', 'getResource'])
            ->getMock();
        $interestGroup->method('getBySubscriberIdStoreId')->willReturnSelf();
        $interestGroup->method('getResource')->willReturn($resource);
        $this->interestGroupFactory->method('create')->willReturn($interestGroup);

        return $interestGroup;
    }

    private function expectNothingWritten(): void
    {
        $this->subscriberFactory->expects($this->never())->method('create');
        $this->interestGroupFactory->expects($this->never())->method('create');
        $this->syncHelper->expects($this->never())->method('saveEcommerceData');
        $this->messageManager->expects($this->never())->method('addSuccessMessage');
    }

    private function expectRedirectToTheStore(): void
    {
        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with($this->response, self::BASE_URL);
    }

    public function testTheControllerOnlyAnswersPost(): void
    {
        $controller = $this->makeController();

        $this->assertInstanceOf(HttpPostActionInterface::class, $controller);
        $this->assertNotInstanceOf(HttpGetActionInterface::class, $controller);
    }

    public function testTheFormPostsWithAFormKey(): void
    {
        $path = __DIR__ . '/../../../../view/frontend/templates/checkout/order/success.phtml';
        $template = file_get_contents($path);

        $this->assertNotFalse($template, "cannot read $path");
        $this->assertSame(1, preg_match('/^.*<form\b.*\bid="mailchimp-groups".*$/m', $template, $form));
        $this->assertStringContainsString('method="post"', $form[0]);
        $this->assertStringNotContainsString('method="get"', $template);
        $this->assertStringContainsString("\$block->getBlockHtml('formkey')", $template);
    }

    public function testNothingIsWrittenWithoutAnOrderInTheSession(): void
    {
        $this->givenTheLastOrder(null);
        $this->requestParams = ['group' => ['abc123' => ['def456' => 'def456']]];

        $this->expectNothingWritten();
        $this->expectRedirectToTheStore();

        $this->assertSame($this->response, $this->makeController()->execute());
    }

    public function testNothingIsWrittenWhenGroupsAfterCheckoutAreOff(): void
    {
        $this->givenTheLastOrder(42);
        $this->interestInSuccess = '0';
        $this->requestParams = ['group' => ['abc123' => ['def456' => 'def456']]];

        $this->expectNothingWritten();
        $this->expectRedirectToTheStore();

        $this->makeController()->execute();
    }

    public function testNothingIsWrittenWhenMailchimpIsDisabledForTheStore(): void
    {
        $this->givenTheLastOrder(42);
        $this->mailchimpEnabled = false;
        $this->requestParams = ['group' => ['abc123' => ['def456' => 'def456']]];

        $this->expectNothingWritten();
        $this->expectRedirectToTheStore();

        $this->makeController()->execute();
    }

    public function testOnlyTheGroupsAreStoredForAnExistingSubscriber(): void
    {
        $this->givenTheLastOrder(42);
        $this->requestParams = [
            'group' => ['abc123' => ['def456' => 'def456'], 'ghi789' => 'jkl012'],
            'form_key' => 'aBcDeFgHiJkLmNoP',
            'anything_else' => 'value',
        ];

        $subscriber = $this->makeSubscriber($this->storedSubscriberData(Subscriber::STATUS_SUBSCRIBED));
        $subscriber->expects($this->never())->method('subscribe');
        $this->subscriberFactory->method('create')->willReturn($subscriber);
        $interestGroup = $this->makeInterestGroup();

        $this->syncHelper->expects($this->once())->method('saveEcommerceData');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');
        $this->expectRedirectToTheStore();

        $this->makeController()->execute();

        $this->assertSame(
            ['group' => ['abc123' => ['def456' => 'def456'], 'ghi789' => 'jkl012']],
            json_decode($interestGroup->getData('groupdata'), true)
        );
        $this->assertSame(7, $interestGroup->getData('subscriber_id'));
        $this->assertSame(self::STORE_ID, $interestGroup->getData('store_id'));
    }

    public function testAnUnsubscribedShopperWhoSubmitsTheFormIsSubscribedAgain(): void
    {
        $this->givenTheLastOrder(42);
        $this->requestParams = ['group' => ['abc123' => ['def456' => 'def456']]];

        $subscriber = $this->makeSubscriber($this->storedSubscriberData(Subscriber::STATUS_UNSUBSCRIBED));
        $subscriber->expects($this->once())->method('subscribe')->with(self::EMAIL);
        $this->subscriberFactory->method('create')->willReturn($subscriber);
        $this->makeInterestGroup();

        $this->expectRedirectToTheStore();

        $this->makeController()->execute();
    }

    public function testANewShopperIsSubscribedAndOnlyTheGroupsAreStored(): void
    {
        $this->givenTheLastOrder(42);
        $this->requestParams = [
            'group' => ['abc123' => 'def456'],
            'form_key' => 'aBcDeFgHiJkLmNoP',
        ];

        $subscriber = $this->makeSubscriber([]);
        $loads = 0;
        $subscriber->method('loadBySubscriberEmail')->willReturnCallback(
            function () use ($subscriber, &$loads) {
                if ($loads++ > 0) {
                    $subscriber->setData($this->storedSubscriberData(Subscriber::STATUS_SUBSCRIBED));
                }
                return $subscriber;
            }
        );
        $subscriber->expects($this->never())->method('subscribe');

        $newcomer = $this->makeSubscriber([]);
        $newcomer->expects($this->once())->method('subscribe')->with(self::EMAIL);

        $this->subscriberFactory->method('create')->willReturnOnConsecutiveCalls($subscriber, $newcomer);
        $interestGroup = $this->makeInterestGroup();

        $this->messageManager->expects($this->once())->method('addSuccessMessage');
        $this->expectRedirectToTheStore();

        $this->makeController()->execute();

        $this->assertSame(
            ['group' => ['abc123' => 'def456']],
            json_decode($interestGroup->getData('groupdata'), true)
        );
    }

    public function testAGroupThatIsNotAnArrayIsStoredAsNoGroups(): void
    {
        $this->givenTheLastOrder(42);
        $this->requestParams = ['group' => 'not-an-array'];

        $subscriber = $this->makeSubscriber($this->storedSubscriberData(Subscriber::STATUS_SUBSCRIBED));
        $this->subscriberFactory->method('create')->willReturn($subscriber);
        $interestGroup = $this->makeInterestGroup();

        $this->makeController()->execute();

        $this->assertSame(['group' => []], json_decode($interestGroup->getData('groupdata'), true));
    }
}
