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

namespace Ebizmarts\MailChimp\Test\Unit\Helper;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\ProductMetadataInterface;
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    /**
     * Only the two inputs are stubbed, so the string under test is built by
     * the real method.
     *
     * @param  string $moduleVersion
     * @param  mixed  $magentoVersion string, or a throwable to raise
     * @return string
     */
    private function agent($moduleVersion, $magentoVersion)
    {
        $metadata = $this->createMock(ProductMetadataInterface::class);
        if ($magentoVersion instanceof \Throwable) {
            $metadata->method('getVersion')->willThrowException($magentoVersion);
        } else {
            $metadata->method('getVersion')->willReturn($magentoVersion);
        }

        $helper = $this->getMockBuilder(MailChimpHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModuleVersion'])
            ->getMock();
        $helper->method('getModuleVersion')->willReturn($moduleVersion);

        $prop = new \ReflectionProperty(MailChimpHelper::class, 'productMetadata');
        $prop->setAccessible(true);
        $prop->setValue($helper, $metadata);

        $method = new \ReflectionMethod(MailChimpHelper::class, 'userAgent');
        $method->setAccessible(true);

        return $method->invoke($helper);
    }

    public function testCarriesBothVersions()
    {
        $this->assertSame(
            'Mailchimp4Magento103.4.82 Magento/2.4.8',
            $this->agent('103.4.82', '2.4.8')
        );
    }

    /**
     * The reader requires the slash, so `Mailchimp4Magento103.4.82` can never
     * be misread as a Magento version.
     */
    public function testPlatformVersionIsSlashPrefixed()
    {
        $this->assertMatchesRegularExpression('~ Magento/2\.4\.8$~', $this->agent('103.4.82', '2.4.8'));
    }

    public function testStaysWellUnderTheHeaderCap()
    {
        $this->assertLessThan(128, strlen($this->agent('103.4.82', '2.4.8')));
    }

    /**
     * Absent beats wrong: a host that cannot answer keeps the old agent rather
     * than reporting an empty or invented platform version.
     */
    public function testFallsBackToTheModuleAgentWhenTheVersionIsEmpty()
    {
        $this->assertSame('Mailchimp4Magento103.4.82', $this->agent('103.4.82', ''));
    }

    public function testFallsBackWhenTheVersionLookupRaises()
    {
        $this->assertSame(
            'Mailchimp4Magento103.4.82',
            $this->agent('103.4.82', new \RuntimeException('no metadata'))
        );
    }

    /**
     * The cross-check looks for the module version inside the agent rather
     * than comparing the whole string, so a second version must not push the
     * first out of reach.
     */
    public function testModuleVersionIsStillFindableInTheAgent()
    {
        $this->assertStringContainsString('103.4.82', $this->agent('103.4.82', '2.4.8'));
    }
}
