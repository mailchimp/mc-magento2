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

namespace Ebizmarts\MailChimp\Test\Unit\Block\Adminhtml;

use Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Fieldset\Hint;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Config\ModuleVersion;
use PHPUnit\Framework\TestCase;

/**
 * The admin warning for an out-of-date library compared version strings with a
 * relational operator, which compares them as strings. '3.0.45' > '3.0.9' is
 * false, so the oldest libraries -- the whole reason the warning exists -- were
 * the ones it could not see.
 */
class LibVersionWarningTest extends TestCase
{
    /**
     * @param  mixed $installed
     * @return Hint
     */
    private function hint($installed)
    {
        $moduleVersion = $this->createMock(ModuleVersion::class);
        $moduleVersion->method('getLibVersion')->willReturn($installed);

        $hint = (new \ReflectionClass(Hint::class))->newInstanceWithoutConstructor();

        $p = new \ReflectionProperty(Hint::class, '_moduleVersion');
        $p->setAccessible(true);
        $p->setValue($hint, $moduleVersion);

        return $hint;
    }

    /**
     * The defect, stated as the case it missed. A store on 3.0.9 is nine
     * releases behind the floor and was told nothing.
     */
    public function testAVersionWithFewerDigitsIsStillOlder()
    {
        $this->assertSame('3.0.9', $this->hint('3.0.9')->checkLibVersion());
    }

    /**
     * @return array
     */
    public static function olderProvider()
    {
        return [
            'nine releases behind'  => ['3.0.9'],
            'one release behind'    => ['3.0.48'],
            'normalised by composer' => ['3.0.48.0'],
            'far behind'            => ['3.0.44'],
        ];
    }

    /**
     * @dataProvider olderProvider
     * @param string $installed
     */
    public function testAnOlderLibraryWarns($installed)
    {
        $this->assertSame($installed, $this->hint($installed)->checkLibVersion());
    }

    /**
     * @return array
     */
    public static function currentProvider()
    {
        return [
            'exactly the floor'      => [MailChimpHelper::MIN_LIB_VERSION],
            'newer'                  => ['3.0.50'],
            'newer past the decade'  => ['3.0.100'],
            'a major ahead'          => ['3.1.0'],
        ];
    }

    /**
     * And the other direction, which the string comparison also got wrong: it
     * would have warned every installation from 3.0.100 onwards.
     *
     * @dataProvider currentProvider
     * @param string $installed
     */
    public function testALibraryAtOrAboveTheFloorIsSilent($installed)
    {
        $this->assertFalse($this->hint($installed)->checkLibVersion());
    }

    /**
     * An app/code installation has no composer metadata to read, so the version
     * arrives empty. Unknown is not the same as current, and warning is the
     * behaviour that was already there.
     */
    public function testAnUnknownVersionStillWarns()
    {
        $this->assertSame('', $this->hint('')->checkLibVersion());
    }

    /**
     * The constant is the only thing an app/code installation is ever told, so
     * it has to agree with the floor composer enforces for everyone else.
     */
    public function testTheConstantTracksTheComposerFloor()
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../../../composer.json'), true);
        $floor = $composer['require']['ebizmarts/mailchimp-lib'];

        $this->assertSame(
            '>=' . MailChimpHelper::MIN_LIB_VERSION,
            $floor,
            'MIN_LIB_VERSION and the composer floor disagree, so app/code installations are told '
            . 'something different from what composer enforces.'
        );
    }
}
