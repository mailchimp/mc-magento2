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

namespace Ebizmarts\MailChimp\Test\Unit\Model\System\Message;

use Ebizmarts\MailChimp\Model\ServiceStatus as ServiceStatusReader;
use Ebizmarts\MailChimp\Model\System\Message\ServiceStatus;
use Magento\Framework\Escaper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class ServiceStatusTest extends TestCase
{
    private function message(array $active)
    {
        $reader = $this->createMock(ServiceStatusReader::class);
        $reader->method('active')->willReturn($active);

        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturn('https://shop.example.com/admin/system_config/edit/section/mailchimp/');

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES);
        });
        $escaper->method('escapeUrl')->willReturnCallback(function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES);
        });

        return new ServiceStatus($reader, $url, $escaper);
    }

    private static function entry($code, $paused = true, array $extra = [], array $stores = ['Main'])
    {
        return [
            'status' => array_merge([
                'code' => $code, 'reason' => 'test', 'ref' => 'lh9', 'iat' => 1, 'exp' => 2,
                'blocks_from' => null, 'blocking' => $paused,
            ], $extra),
            'stores' => $stores,
        ];
    }

    public function testNothingHeldNothingShown(): void
    {
        $this->assertFalse($this->message([])->isDisplayed());
    }

    public function testNoLongerSupported(): void
    {
        $text = $this->message([self::entry('DEPRECATED')])->getText();

        $this->assertStringContainsString('This version is no longer supported.', $text);
        $this->assertStringContainsString('mailchimp@help.ebizmarts.com', $text);
        $this->assertStringContainsString('lh9', $text);
        $this->assertStringNotContainsString('soon', $text);
        $this->assertStringNotContainsString('<a ', $text);
        $this->assertStringNotContainsString('kept', $text);
    }

    public function testPausedKeepsData(): void
    {
        $text = $this->message([self::entry('BLOCK')])->getText();

        $this->assertStringContainsString('Mailchimp sync is paused by the service.', $text);
        $this->assertStringContainsString('Your data and settings are kept.', $text);
        $this->assertStringContainsString('mailchimp@help.ebizmarts.com', $text);
    }

    public function testUpgrade(): void
    {
        $this->assertStringContainsString(
            'needs to be updated',
            $this->message([self::entry('UPGRADE')])->getText()
        );
    }

    public function testNewTokenLinksToTheConfiguration(): void
    {
        $text = $this->message([self::entry('NEW_TOKEN')])->getText();

        $this->assertStringContainsString('reconnected', $text);
        $this->assertStringContainsString('href="https://shop.example.com/admin/system_config/edit/section/mailchimp/"', $text);
    }

    public function testAFutureStartIsAWarningWithTheDate(): void
    {
        $from = gmmktime(9, 30, 0, 12, 1, 2026);
        $message = $this->message([self::entry('DEPRECATED', false, ['blocks_from' => $from])]);

        $this->assertStringContainsString('will be paused by the service on 2026-12-01 09:30', $message->getText());
        $this->assertSame(MessageInterface::SEVERITY_MAJOR, $message->getSeverity());
    }

    public function testAPausingStatusIsCritical(): void
    {
        $this->assertSame(
            MessageInterface::SEVERITY_CRITICAL,
            $this->message([self::entry('BLOCK')])->getSeverity()
        );
    }

    public function testStoreNamesAreEscaped(): void
    {
        $text = $this->message([self::entry('BLOCK', true, [], ['<b>Shop</b>'])])->getText();

        $this->assertStringNotContainsString('<b>Shop</b>', $text);
        $this->assertStringContainsString('&lt;b&gt;Shop&lt;/b&gt;', $text);
    }

    public function testANewStatusIsANewMessage(): void
    {
        $a = $this->message([self::entry('BLOCK')])->getIdentity();
        $b = $this->message([self::entry('BLOCK', true, ['ref' => 'lh10'])])->getIdentity();

        $this->assertNotSame($a, $b);
    }

    public function testEveryCodeSaysUnsubscribesAreNotSent(): void
    {
        foreach (['BLOCK', 'NEW_TOKEN', 'UPGRADE', 'DEPRECATED'] as $code) {
            $text = $this->message([self::entry($code)])->getText();
            $this->assertStringContainsString(
                'While sync is paused, changes made in Magento, including unsubscribes, are not sent to Mailchimp.',
                $text,
                $code
            );
            $this->assertStringNotContainsString('ebizmarts.', str_replace('help.ebizmarts.com', '', $text), $code);
        }
    }

    public function testAWarningSaysWhatWillStopBeingSent(): void
    {
        $text = $this->message([self::entry('BLOCK', false, ['blocks_from' => 1800000000])])->getText();

        $this->assertStringContainsString('From then on, changes made in Magento, including unsubscribes', $text);
    }

    public function testTheSameStatusSignedAgainIsTheSameMessage(): void
    {
        $a = $this->message([self::entry('BLOCK', true, ['iat' => 100, 'exp' => 200])])->getIdentity();
        $b = $this->message([self::entry('BLOCK', true, ['iat' => 150, 'exp' => 250])])->getIdentity();

        $this->assertSame($a, $b);
    }

    public function testAWarningThatStartsPausingIsANewMessage(): void
    {
        $a = $this->message([self::entry('BLOCK', false, ['blocks_from' => 1800000000])])->getIdentity();
        $b = $this->message([self::entry('BLOCK', true, ['blocks_from' => 1800000000])])->getIdentity();

        $this->assertNotSame($a, $b);
    }
}
