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

namespace Ebizmarts\MailChimp\Test\Unit\Controller\Adminhtml\Ecommerce;

use Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce\Register;
use Ebizmarts\MailChimp\Helper\Data as Helper;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

/**
 * The register response must be an array with a non-empty token before it is read.
 */
class RegisterTest extends TestCase
{
    /** @var array  every saveConfigValue() call, path => value */
    private $saved = [];

    /** @var array|null  what execute() handed to setData() */
    private $jsonData = null;

    /**
     * @param  string $registerResponse  body the register service returns
     * @return Register
     */
    private function makeRegister($registerResponse)
    {
        $this->saved = [];
        $this->jsonData = null;

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParams')->willReturn([
            'scope' => 'default',
            'scopeId' => 0,
            'data' => [
                'account_name' => ['value' => 'Acme'],
                'email' => ['value' => 'a@example.com'],
            ],
        ]);

        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) {
            $this->jsonData = $data;
        });

        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturn($json);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $helper = $this->createStub(Helper::class);
        $helper->method('getConfigValue')->willReturnCallback(
            function ($path, $storeId = null, $scope = null) {
                switch ($path) {
                    case Helper::XML_REGISTER_URL:
                        return 'https://users-mc4mage.ebizmarts.com';
                    case Helper::XML_STATISTICS_TOKEN:
                        return '';
                    default:
                        return 'placeholder';
                }
            }
        );
        $helper->method('saveConfigValue')->willReturnCallback(function ($path, $value) {
            $this->saved[$path] = $value;
        });

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.example.com/');

        $storeManager = $this->createStub(StoreManager::class);
        $storeManager->method('getStore')->willReturn($store);

        $http = $this->createStub(MailChimpHttp::class);
        $http->method('post')->willReturn($registerResponse);
        $http->method('patch')->willReturn($registerResponse);

        $directory = $this->createStub(DirectoryHelper::class);
        $directory->method('getDefaultCountry')->willReturn('US');

        return new Register(
            $context,
            $helper,
            $http,
            $this->createStub(Config::class),
            $storeManager,
            $directory
        );
    }

    public function testANonJsonRegisterResponseIsAnErrorNotACrash(): void
    {
        $register = $this->makeRegister('<title>500 Internal Server Error</title>');

        $register->execute();

        $this->assertSame(['error' => true, 'token' => ''], $this->jsonData);
        $this->assertSame([], $this->saved, 'a failed registration must not save a token');
    }

    public function testAValidRegisterResponseStillSavesTheToken(): void
    {
        $register = $this->makeRegister('{"error": false, "token": "ebz_abc"}');

        $register->execute();

        $this->assertSame(['error' => false, 'token' => 'ebz_abc'], $this->jsonData);
        $this->assertSame('ebz_abc', $this->saved[Helper::XML_STATISTICS_TOKEN]);
    }

    public function testAJsonResponseWithoutAnErrorKeyIsAnErrorNotACrash(): void
    {
        $register = $this->makeRegister('{"message":"Unauthorized"}');

        $register->execute();

        $this->assertSame(['error' => true, 'token' => ''], $this->jsonData);
        $this->assertSame([], $this->saved, 'a response without error or token must not save a token');
    }
}
