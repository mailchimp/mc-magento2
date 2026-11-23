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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Config\Source;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;
use Ebizmarts\MailChimp\Model\Config\Source\Details;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * The register response must be an array with a non-empty token before it is read.
 */
class DetailsTest extends TestCase
{
    /** @var array  every saveConfigValue() call, path => value */
    private $saved = [];

    /**
     * @param  string $registerResponse  body the register service returns
     * @return Details
     */
    private function makeDetails($registerResponse)
    {
        $this->saved = [];

        $helper = $this->createStub(MailChimpHelper::class);
        $helper->method('getApiKey')->willReturn('key-us1');
        $helper->method('getApi')->willReturn($this->makeApi());
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path, $storeId = null, $scope = null) {
                switch ($path) {
                    case MailChimpHelper::XML_REGISTER_URL:
                        return 'https://users-mc4mage.ebizmarts.com';
                    case MailChimpHelper::XML_MAILCHIMP_STORE:
                        return 'mc-store-123';
                    case MailChimpHelper::XML_PATH_ECOMMERCE_ACTIVE:
                        return 1;
                    case MailChimpHelper::XML_STATISTICS_TOKEN:
                        return '';
                    default:
                        return 'placeholder';
                }
            }
        );
        $helper->method('saveConfigValue')->willReturnCallback(function ($path, $value) {
            $this->saved[$path] = $value;
        });
        $helper->method('getMCMinSyncDateFlagByMailchimpStore')->willReturn('2024-01-01');

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.example.com/');

        $storeManager = $this->createStub(StoreManager::class);
        $storeManager->method('getStore')->willReturn($store);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn(0);

        $http = $this->createStub(MailChimpHttp::class);
        $http->method('post')->willReturn($registerResponse);

        $directory = $this->createStub(DirectoryHelper::class);
        $directory->method('getDefaultCountry')->willReturn('US');

        return new Details(
            $helper,
            $this->createStub(ManagerInterface::class),
            $storeManager,
            $request,
            $http,
            $directory
        );
    }

    /**
     * A minimal Mailchimp API stand-in: enough of the graph the constructor
     * walks after the register call to return a well-formed option list.
     *
     * @return object
     */
    private function makeApi()
    {
        $root = new class {
            public function info()
            {
                return [
                    'account_name' => 'Acme',
                    'email' => 'a@example.com',
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                    'pricing_plan_type' => 'paid',
                    'username' => 'acme',
                    'account_id' => 'acc-1',
                    'total_subscribers' => 100,
                ];
            }
        };
        $lists = new class {
            public function getLists(...$args)
            {
                return null;
            }
        };
        $stores = new class {
            public function get($id)
            {
                return ['is_syncing' => false];
            }
        };
        $totals = new class {
            public function getAll($id, $type)
            {
                return ['total_items' => 1];
            }
        };
        $ecommerce = new \stdClass();
        $ecommerce->stores = $stores;
        $ecommerce->customers = $totals;
        $ecommerce->products = $totals;
        $ecommerce->orders = $totals;
        $ecommerce->carts = $totals;

        $api = new \stdClass();
        $api->root = $root;
        $api->lists = $lists;
        $api->ecommerce = $ecommerce;

        return $api;
    }

    public function testANonJsonRegisterResponseDoesNotCrashTheConfigPage(): void
    {
        $details = $this->makeDetails('<title>500 Internal Server Error</title>');

        $this->assertInstanceOf(Details::class, $details);
        $this->assertSame([], $this->saved, 'a failed registration must not save a token');
    }

    public function testAValidRegisterResponseStillSavesTheToken(): void
    {
        $this->makeDetails('{"error": false, "token": "ebz_abc"}');

        $this->assertSame('ebz_abc', $this->saved[MailChimpHelper::XML_STATISTICS_TOKEN]);
    }

    public function testAJsonResponseWithoutAnErrorKeyDoesNotSaveAToken(): void
    {
        $this->makeDetails('[]');

        $this->assertSame([], $this->saved, 'a response without error or token must not save a token');
    }
}
