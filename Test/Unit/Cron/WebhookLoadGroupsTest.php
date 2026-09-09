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

    /** @var bool  whether the account call has already returned a verdict */
    private $credentialAlreadyRejected = false;

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
        $failed = $this->credentialAlreadyRejected;

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isMailChimpEnabled')->willReturn(true);
        $helper->method('getDefaultList')->willReturn('list-1');
        $helper->method('getApi')->willReturnCallback(function () {
            $this->getApiCalls++;
            return $this->rejectingApi();
        });
        // This loop reads the verdict and never writes one: the call it makes
        // carries an audience id, so its failure can mean a wrong audience on a
        // perfectly good key. The verdict comes from the account call in the
        // ecommerce job, which runs first in the shared cron process.
        $helper->expects($this->never())->method('markApiKeyFailed');
        $helper->method('isApiKeyFailed')->willReturnCallback(function () use (&$failed) {
            return $failed;
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
     * dead key cost one rejected call per store view, every run. Once the
     * account call has returned its verdict, they cost nothing.
     */
    public function testAKnownRejectedKeyCostsNothingHere()
    {
        $this->getApiCalls = 0;
        $this->credentialAlreadyRejected = true;
        $this->loadGroups($this->cronWithViews(28));

        $this->assertSame(0, $this->getApiCalls);
    }

    /**
     * And with no verdict in hand it still does its work rather than guessing.
     */
    public function testWithoutAVerdictTheLoopStillRuns()
    {
        $this->getApiCalls = 0;
        $this->credentialAlreadyRejected = false;
        $this->loadGroups($this->cronWithViews(3));

        $this->assertSame(3, $this->getApiCalls);
    }

    /**
     * The point of the review: a failure on this call carries an audience id,
     * so it must not be allowed to condemn the credential.
     */
    public function testAFailureHereNeverCondemnsTheCredential()
    {
        $this->getApiCalls = 0;
        $this->credentialAlreadyRejected = false;
        $this->loadGroups($this->cronWithViews(5));

        $this->assertSame(5, $this->getApiCalls);   // markApiKeyFailed asserted never above
    }
}
