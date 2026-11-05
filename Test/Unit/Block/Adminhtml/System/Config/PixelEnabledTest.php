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

namespace Ebizmarts\MailChimp\Test\Unit\Block\Adminhtml\System\Config;

use Ebizmarts\MailChimp\Block\Adminhtml\System\Config\PixelEnabled;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Which store view the Pixel toggle is rendered for.
 *
 * The toggle is locked until that store view is connected, so getting the
 * store view wrong either locks it for good or opens it where it should not
 * be. Single-store mode is the case that needs care: Magento shows
 * store-view-only fields on the Default Config page there, with no `store`
 * parameter at all.
 */
class PixelEnabledTest extends TestCase
{
    /**
     * Built without its constructor, as OauthWizardTest does: the block's
     * parent reaches `ObjectManager::getInstance()` while constructing, and the
     * method under test only reads the request and the store manager.
     *
     * @param  string|null $storeParam
     * @param  bool        $singleStoreMode
     * @param  int|null    $defaultStoreViewId  null for no default store view
     * @return int|null
     */
    private function resolve($storeParam, bool $singleStoreMode, $defaultStoreViewId = 1)
    {
        $block = (new \ReflectionClass(PixelEnabled::class))->newInstanceWithoutConstructor();

        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('store')->willReturn($storeParam);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('isSingleStoreMode')->willReturn($singleStoreMode);
        if ($defaultStoreViewId === null) {
            $storeManager->method('getDefaultStoreView')->willReturn(null);
        } else {
            $store = $this->createMock(StoreInterface::class);
            $store->method('getId')->willReturn($defaultStoreViewId);
            $storeManager->method('getDefaultStoreView')->willReturn($store);
        }

        $this->inject($block, '_request', $request);
        $this->inject($block, '_storeManager', $storeManager);

        $method = new \ReflectionMethod(PixelEnabled::class, 'resolveStoreId');
        $method->setAccessible(true);

        return $method->invoke($block);
    }

    private function inject($object, string $property, $value): void
    {
        $class = new \ReflectionClass($object);
        while ($class && !$class->hasProperty($property)) {
            $class = $class->getParentClass();
        }
        $p = $class->getProperty($property);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    public function testTheStoreParameterIsUsedWhenPresent(): void
    {
        $this->assertSame(4, $this->resolve('4', false));
    }

    /**
     * The store parameter wins even in single-store mode: if Magento ever does
     * send one, it is the store view the page is about.
     */
    public function testTheStoreParameterWinsInSingleStoreModeToo(): void
    {
        $this->assertSame(4, $this->resolve('4', true, 1));
    }

    /**
     * Multi-store: no store parameter means the field is not on a store-view
     * page, and "not connected" -- locked -- is the safe answer.
     */
    public function testNoStoreParameterOnAMultiStoreInstallIsUnknown(): void
    {
        $this->assertNull($this->resolve(null, false));
        $this->assertNull($this->resolve('', false));
    }

    /**
     * The fix: single-store mode never has a store parameter, so without the
     * fallback the toggle stayed locked for good, even after connecting.
     */
    public function testSingleStoreModeFallsBackToTheOneStoreView(): void
    {
        $this->assertSame(1, $this->resolve(null, true, 1));
    }

    public function testSingleStoreModeWithNoDefaultStoreViewIsUnknown(): void
    {
        $this->assertNull($this->resolve(null, true, null));
    }
}
