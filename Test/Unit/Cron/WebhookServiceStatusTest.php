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

namespace Ebizmarts\MailChimp\Test\Unit\Cron;

use Ebizmarts\MailChimp\Cron\Webhook;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory;
use Ebizmarts\MailChimp\Model\ServiceStatus;
use Magento\Customer\Model\CustomerFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * While the QoS service pauses a store, the audience's interest tree cannot be
 * read, so a profile webhook's GROUPINGS must not replace the customer's
 * stored interest selections with empty ones.
 */
class WebhookServiceStatusTest extends TestCase
{
    /** @var MailChimpInterestGroupFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $interestGroupFactory;

    /** @var SubscriberFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $subscriberFactory;

    private function cron($paused)
    {
        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getMapFields')->willReturn([]);
        $helper->method('serialize')->willReturnCallback('json_encode');
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getDefaultList')->willReturn('list-1');

        $subscriber = $this->getMockBuilder(\Magento\Newsletter\Model\Subscriber::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByCustomer', 'getEmail'])
            ->addMethods(['getSubscriberId', 'getStoreId'])
            ->getMock();
        $subscriber->method('getEmail')->willReturn('shopper@example.com');
        $subscriber->method('getSubscriberId')->willReturn(7);
        $subscriber->method('getStoreId')->willReturn(1);
        $this->subscriberFactory = $this->createMock(SubscriberFactory::class);
        $this->subscriberFactory->method('create')->willReturn($subscriber);

        $resource = new class {
            public function save($object)
            {
                return $this;
            }
        };
        $interestGroup = $this->getMockBuilder(\Ebizmarts\MailChimp\Model\MailChimpInterestGroup::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBySubscriberIdStoreId', 'getResource'])
            ->getMock();
        $interestGroup->method('getResource')->willReturn($resource);
        $this->interestGroupFactory = $this->createMock(MailChimpInterestGroupFactory::class);
        $this->interestGroupFactory->method('create')->willReturn($interestGroup);

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn([]);

        $cron = new Webhook(
            $helper,
            $this->subscriberFactory,
            $this->createMock(CollectionFactory::class),
            $this->interestGroupFactory,
            $storeManager,
            $this->createMock(CustomerFactory::class)
        );

        $serviceStatus = $this->createMock(ServiceStatus::class);
        $serviceStatus->method('isPaused')->willReturn($paused);
        $property = new \ReflectionProperty(Webhook::class, 'serviceStatus');
        $property->setAccessible(true);
        $property->setValue($cron, $serviceStatus);

        return $cron;
    }

    private function processMerges(Webhook $cron)
    {
        $customer = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getStoreId', 'getWebsiteId', 'getEmail'])
            ->getMock();
        $customer->method('getStoreId')->willReturn(1);
        $customer->method('getWebsiteId')->willReturn(1);
        $customer->method('getId')->willReturn(3);
        $customer->method('getEmail')->willReturn('shopper@example.com');

        $method = new \ReflectionMethod(Webhook::class, '_processMerges');
        $method->setAccessible(true);
        $method->invoke($cron, $customer, ['merges' => [
            'GROUPINGS' => [['unique_id' => 'cat-1', 'groups' => 'Weekly']],
        ]]);
    }

    public function testAPausedStoreKeepsTheStoredInterestSelections(): void
    {
        $cron = $this->cron(true);

        $this->interestGroupFactory->expects($this->never())->method('create');
        $this->subscriberFactory->expects($this->never())->method('create');

        $this->processMerges($cron);
    }

    public function testAStoreThatIsNotPausedStillSavesThem(): void
    {
        $cron = $this->cron(false);
        $property = new \ReflectionProperty(Webhook::class, 'groupsLoaded');
        $property->setAccessible(true);
        $property->setValue($cron, true);

        $this->interestGroupFactory->expects($this->once())->method('create');

        $this->processMerges($cron);
    }
}
