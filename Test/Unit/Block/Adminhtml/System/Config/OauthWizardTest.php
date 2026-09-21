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

namespace Ebizmarts\MailChimp\Test\Unit\Block\Adminhtml\System\Config;

use Ebizmarts\MailChimp\Block\Adminhtml\System\Config\OauthWizard;
use PHPUnit\Framework\TestCase;

class OauthWizardTest extends TestCase
{
    /**
     * Built without its constructor on purpose.
     *
     * The block's parent reaches `ObjectManager::getInstance()` while
     * constructing, which a unit test has no business standing up, and the
     * method under test reads nothing the constructor sets.
     */
    private function makeBlock(): OauthWizard
    {
        return (new \ReflectionClass(OauthWizard::class))->newInstanceWithoutConstructor();
    }

    public function testTheConnectButtonPointsAtTheRelay(): void
    {
        $this->assertSame(
            'https://apps.ebizmarts.com/mc4magento/v1/mc-magento2/connect/go',
            $this->makeBlock()->authorizeRequestUrl()
        );
    }

    /**
     * The property this change exists to establish, pinned so it cannot be
     * undone by accident.
     *
     * The connect flow used to begin at Mailchimp, which meant the extension
     * had to carry the authorize endpoint, the redirect URI and the client id
     * -- in a released artefact, on a merchant's disk, where changing any of
     * them costs a release that reaches a fraction of the installed base over
     * months. It now begins at the relay, and none of the three has a reason
     * to come back.
     *
     * Asserted over the source rather than over this block, because the next
     * one would not necessarily be added here: a second block already builds a
     * popup the same way, and that is how one of these spreads.
     *
     * `admin.mailchimp.com` is deliberately NOT in this list. It is a deep link
     * into the merchant's own Mailchimp account from the abandoned-cart
     * setting, carries no credential, and is not part of any flow.
     */
    public function testNoMailchimpOauthEndpointOrCredentialRemainsInTheExtension(): void
    {
        $needles = [
            'login.mailchimp.com/oauth2',
            'oauth2/complete.php',
            '390007044048',
        ];

        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            if ($file === __FILE__) {
                continue;
            }

            $source = file_get_contents($file);

            foreach ($needles as $needle) {
                if (strpos($source, $needle) !== false) {
                    $offenders[] = basename($file) . ' -> ' . $needle;
                }
            }
        }

        $this->assertSame([], $offenders, implode(', ', $offenders));
    }

    /**
     * @return string[] every .php and .phtml file in the extension
     */
    private function sourceFiles(): array
    {
        $root = dirname(__DIR__, 6);

        $found = [];
        $walk  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($walk as $entry) {
            $path = $entry->getPathname();

            if (preg_match('/\.(php|phtml)$/', $path) === 1) {
                $found[] = $path;
            }
        }

        return $found;
    }
}
