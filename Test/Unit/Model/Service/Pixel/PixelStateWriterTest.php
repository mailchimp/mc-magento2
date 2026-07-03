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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Service\Pixel;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Service\Pixel\PixelStateWriter;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class PixelStateWriterTest extends TestCase
{
    private WriterInterface $configWriter;
    private TypeListInterface $cacheTypeList;
    private PixelStateWriter $writer;

    protected function setUp(): void
    {
        $this->configWriter  = $this->createMock(WriterInterface::class);
        $this->cacheTypeList = $this->createMock(TypeListInterface::class);
        $this->writer = new PixelStateWriter($this->configWriter, $this->cacheTypeList);
    }

    /**
     * The critical invariant: enabled_for_store must be saved LAST.
     * If it were saved first and the fragment save failed, the store would be
     * "enabled but without a script".
     */
    public function testEnableSavesInCorrectOrder(): void
    {
        $storeId        = 1;
        $scriptUrl      = 'https://chimpstatic.com/mcjs-connected/js/users/abc.js';
        $scriptFragment = '<script>/* fragment */</script>';

        $callOrder = [];

        $this->configWriter
            ->expects($this->exactly(3))
            ->method('save')
            ->willReturnCallback(function (string $path) use (&$callOrder) {
                $callOrder[] = $path;
            });

        $this->cacheTypeList->expects($this->once())->method('invalidate')->with('config');

        $this->writer->enable($storeId, $scriptUrl, $scriptFragment);

        $this->assertSame(
            [
                MailChimpHelper::XML_PIXEL_SCRIPT_URL,
                MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT,
                MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE,
            ],
            $callOrder,
            'enabled_for_store must be saved last to avoid "enabled but without script" state'
        );
    }

    public function testEnableSavesCorrectValues(): void
    {
        $storeId        = 2;
        $scriptUrl      = 'https://chimpstatic.com/mcjs-connected/js/users/xyz.js';
        $scriptFragment = '<script>/* pixel fragment */</script>';

        $saved = [];

        $this->configWriter
            ->method('save')
            ->willReturnCallback(function (string $path, $value, string $scope, int $sid) use (&$saved) {
                $saved[$path] = ['value' => $value, 'scope' => $scope, 'storeId' => $sid];
            });

        $this->writer->enable($storeId, $scriptUrl, $scriptFragment);

        $this->assertSame($scriptUrl,      $saved[MailChimpHelper::XML_PIXEL_SCRIPT_URL]['value']);
        $this->assertSame($scriptFragment, $saved[MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT]['value']);
        $this->assertSame(1,               $saved[MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE]['value']);

        foreach ($saved as $entry) {
            $this->assertSame(ScopeInterface::SCOPE_STORES, $entry['scope']);
            $this->assertSame($storeId, $entry['storeId']);
        }
    }

    /**
     * On disable: clear data before setting the flag to 0,
     * so the store is never "enabled but without a script".
     */
    public function testDisableClearsDataBeforeFlag(): void
    {
        $storeId   = 1;
        $callOrder = [];

        $this->configWriter
            ->expects($this->exactly(3))
            ->method('save')
            ->willReturnCallback(function (string $path) use (&$callOrder) {
                $callOrder[] = $path;
            });

        $this->cacheTypeList->expects($this->once())->method('invalidate')->with('config');

        $this->writer->disable($storeId);

        $this->assertSame(MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE, end($callOrder),
            'enabled_for_store must be set to 0 last when disabling');

        $this->assertContains(MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT, $callOrder);
        $this->assertContains(MailChimpHelper::XML_PIXEL_SCRIPT_URL, $callOrder);
    }

    public function testDisableSetsEmptyValuesAndZeroFlag(): void
    {
        $saved = [];

        $this->configWriter
            ->method('save')
            ->willReturnCallback(function (string $path, $value) use (&$saved) {
                $saved[$path] = $value;
            });

        $this->writer->disable(1);

        $this->assertSame('', $saved[MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT]);
        $this->assertSame('', $saved[MailChimpHelper::XML_PIXEL_SCRIPT_URL]);
        $this->assertSame(0,  $saved[MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE]);
    }

    public function testEnableInvalidatesConfigCache(): void
    {
        $this->configWriter->method('save');
        $this->cacheTypeList->expects($this->once())->method('invalidate')->with('config');

        $this->writer->enable(1, 'https://example.com/pixel.js', '<script></script>');
    }

    public function testDisableInvalidatesConfigCache(): void
    {
        $this->configWriter->method('save');
        $this->cacheTypeList->expects($this->once())->method('invalidate')->with('config');

        $this->writer->disable(1);
    }
}
