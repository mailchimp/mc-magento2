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

use Ebizmarts\MailChimp\Cron\Ecommerce;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\ServiceStatus;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * A store view whose calls the QoS service has paused is skipped before the
 * sync touches Mailchimp, so it does not log the same failure every run.
 */
class EcommerceServiceStatusTest extends TestCase
{
    /** @var MailChimpHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    private function makeCron($paused)
    {
        $storeManager = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $storeManager->method('getStores')->willReturn([1 => 'store']);

        $this->helper = $this->createMock(MailChimpHelper::class);
        $this->helper->method('getConfigValue')->willReturn(true);

        $connection = $this->createMock(AdapterInterface::class);
        $resource = $this->getMockBuilder(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getMainTable'])
            ->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('mailchimp_sync_ecommerce');
        $syncEcommerce = $this->getMockBuilder(\Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResource'])
            ->getMock();
        $syncEcommerce->method('getResource')->willReturn($resource);

        $serviceStatus = $this->createMock(ServiceStatus::class);
        $serviceStatus->method('isPaused')->willReturn($paused);

        return new Ecommerce(
            $storeManager,
            $this->helper,
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Product::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Result::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Customer::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Order::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Cart::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\Subscriber::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\PromoCodes::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\Api\PromoRules::class),
            $this->createMock(\Ebizmarts\MailChimp\Model\MailChimpSyncBatchesFactory::class),
            $syncEcommerce,
            $this->createMock(\Magento\Framework\Filesystem\DirectoryList::class),
            $serviceStatus
        );
    }

    public function testAPausedStoreIsSkippedWithoutACall(): void
    {
        $cron = $this->makeCron(true);

        // Handed to the library on purpose, so its hourly status check keeps
        // running for the key; the client it returns is never called.
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->expects($this->never())->method('info');
        $api->root = $root;
        $this->helper->expects($this->once())->method('getApi')->with(1)->willReturn($api);
        $this->helper->expects($this->never())->method('log');

        $cron->execute();
    }

    public function testAStoreThatIsNotPausedIsPinged(): void
    {
        $cron = $this->makeCron(false);

        // _ping() asks for the API. A ping that fails is enough to show the
        // store was not skipped, and stops the run before the sync itself.
        $root = $this->getMockBuilder(\Mailchimp_Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();
        $root->method('info')->willThrowException(new \Mailchimp_Error('https://us1.api.mailchimp.com/3.0/'));
        $api = $this->getMockBuilder(\Mailchimp::class)->disableOriginalConstructor()->getMock();
        $api->root = $root;
        $this->helper->expects($this->atLeastOnce())->method('getApi')->willReturn($api);

        $cron->execute();
    }
}
