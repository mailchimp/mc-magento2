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
use Magento\Customer\Model\CustomerFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

class WebhookLoadGroupsTest extends TestCase
{
    /** @var int */
    private $getApiCalls = 0;

    /**
     * An API object whose only job is to reject the call, the way a store with
     * a dead key does.
     *
     * @return object
     */
    private function rejectingApi()
    {
        $category = new class {
            public function getAll($listId, $a = null, $b = null, $c = null)
            {
                throw new \Mailchimp_Error('/lists', 'GET', '', 'API Key Invalid', 'Your API key may be invalid');
            }
        };

        $lists = new \stdClass();
        $lists->interestCategory = $category;

        $api = new \stdClass();
        $api->lists = $lists;

        return $api;
    }

    /**
     * @param  int $storeViews
     * @return Webhook
     */
    private function cronWithViews($storeViews)
    {
        $failed = [];

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getDefaultList')->willReturn('list-1');
        $helper->method('getApi')->willReturnCallback(function () {
            $this->getApiCalls++;
            return $this->rejectingApi();
        });
        // One credential across every view, which is the shape that turns a
        // single rejection into one round trip per view.
        $helper->method('markApiKeyFailed')->willReturnCallback(function () use (&$failed) {
            $failed['shared'] = true;
        });
        $helper->method('isApiKeyFailed')->willReturnCallback(function () use (&$failed) {
            return isset($failed['shared']);
        });

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')
            ->willReturn(array_fill_keys(range(1, $storeViews), new \stdClass()));

        return new Webhook(
            $helper,
            $this->createMock(SubscriberFactory::class),
            $this->createMock(CollectionFactory::class),
            $this->createMock(MailChimpInterestGroupFactory::class),
            $storeManager,
            $this->createMock(CustomerFactory::class)
        );
    }

    /**
     * @param  Webhook $cron
     * @return void
     */
    private function loadGroups(Webhook $cron)
    {
        $method = new \ReflectionMethod(Webhook::class, '_loadGroups');
        $method->setAccessible(true);
        $method->invoke($cron);
    }

    /**
     * The defect: this loop runs before any webhook work is looked at, so a
     * dead key used to cost one rejected call per store view, every run.
     */
    public function testARejectedKeyIsAskedOnceNotOncePerStoreView()
    {
        $this->getApiCalls = 0;
        $this->loadGroups($this->cronWithViews(28));

        $this->assertSame(1, $this->getApiCalls);
    }

    public function testASingleViewStillAsksOnce()
    {
        $this->getApiCalls = 0;
        $this->loadGroups($this->cronWithViews(1));

        $this->assertSame(1, $this->getApiCalls);
    }
}
