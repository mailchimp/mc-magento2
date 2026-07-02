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

namespace Ebizmarts\MailChimp\Test\Unit\Block\Pixel;

use Ebizmarts\MailChimp\Block\Pixel\Script;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    private function makeBlock(
        bool $pixelEnabled,
        string $scriptUrl,
        int $websiteId,
        bool $cookieRestriction
    ): Script {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn($websiteId);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with('web/cookie/cookie_restriction', ScopeInterface::SCOPE_STORE, 1)
            ->willReturn($cookieRestriction ? '1' : '0');

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('isPixelEnabled')->with(1)->willReturn($pixelEnabled);
        $helper->method('getPixelScriptUrl')->with(1)->willReturn($scriptUrl);
        $helper->method('getWebsiteId')->with(1)->willReturn($websiteId);

        $context = $this->getMockBuilder(Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->method('getStoreManager')->willReturn($storeManager);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Script($context, $helper);
    }

    public function testGetPixelConfigReturnsNullWhenPixelDisabled(): void
    {
        $block = $this->makeBlock(false, 'https://example.com/pixel.js', 1, false);

        $this->assertNull($block->getPixelConfig());
    }

    public function testGetPixelConfigReturnsNullWhenScriptUrlEmpty(): void
    {
        $block = $this->makeBlock(true, '', 1, false);

        $this->assertNull($block->getPixelConfig());
    }

    public function testGetPixelConfigReturnsCorrectStructure(): void
    {
        $block  = $this->makeBlock(true, 'https://chimpstatic.com/pixel.js', 3, false);
        $result = $block->getPixelConfig();

        $this->assertNotNull($result);
        $this->assertSame('https://chimpstatic.com/pixel.js', $result['scriptUrl']);
        $this->assertSame(3, $result['websiteId']);
        $this->assertFalse($result['restrictionMode']);
    }

    public function testGetPixelConfigReflectsCookieRestrictionMode(): void
    {
        $block  = $this->makeBlock(true, 'https://chimpstatic.com/pixel.js', 1, true);
        $result = $block->getPixelConfig();

        $this->assertTrue($result['restrictionMode']);
    }

    public function testGetPixelConfigWebsiteIdIsInteger(): void
    {
        $block  = $this->makeBlock(true, 'https://chimpstatic.com/pixel.js', 5, false);
        $result = $block->getPixelConfig();

        $this->assertIsInt($result['websiteId']);
    }
}
