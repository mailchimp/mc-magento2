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

namespace Ebizmarts\MailChimp\Test\Unit\Controller\Adminhtml;

use PHPUnit\Framework\TestCase;

/**
 * The two admin buttons that download a batch response.
 *
 * Both read `getBatchResponse()`, which has three outcomes and used to have
 * two. Their guard was `$files === false`, so once "not finished" became `null`
 * it fell through to `foreach ($files as &$file)` -- a PHP 8 warning, written
 * into the output stream of a JSON download, once per retry because
 * `$fileContent` stays empty and the do/while goes round again.
 *
 * These read the source rather than executing the controllers: both are
 * Magento backend actions whose construction needs a full backend context, and
 * what is worth pinning is the guard itself, which is a one-character edit away
 * from returning.
 */
class BatchResponseDownloadTest extends TestCase
{
    /**
     * @return array
     */
    public static function controllerProvider()
    {
        return [
            'the batch response button'  => [__DIR__ . '/../../../../Controller/Adminhtml/Batch/GetResponse.php'],
            'the errors response button' => [__DIR__ . '/../../../../Controller/Adminhtml/Errors/Getresponse.php'],
        ];
    }

    /**
     * @dataProvider controllerProvider
     * @param string $path
     */
    public function testNothingButAnArrayReachesTheLoop($path)
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source, "cannot read $path");

        // The two controllers differ by one character here: one iterates by
        // reference and the other does not. Matching the common prefix keeps
        // this about the guard rather than about that difference.
        $loop = strpos($source, 'foreach ($files as');
        $this->assertNotFalse($loop, 'the loop this guards has moved or been renamed');

        $before = substr($source, 0, $loop);

        $this->assertStringContainsString(
            '$files === false',
            $before,
            'the deleted-from-Mailchimp case no longer returns before the loop'
        );
        $this->assertStringContainsString(
            '$files === null',
            $before,
            'an unfinished batch reaches foreach(), which warns on PHP 8 and corrupts a JSON download'
        );
    }

    /**
     * Each guard has to leave, not merely assign.
     *
     * Both branches set `$fileContent` to a string, and the loop's own
     * condition is `while (!count($fileContent) …)`. Falling out of a branch
     * without returning therefore calls count() on a string, which on PHP 8 is
     * a TypeError rather than a warning -- the admin button 500s instead of
     * downloading anything. Verified by removing the break and executing the
     * controller.
     *
     * @dataProvider controllerProvider
     * @param string $path
     */
    public function testEachGuardLeavesTheLoop($path)
    {
        $source = file_get_contents($path);

        foreach (['$files === false', '$files === null'] as $guard) {
            $start = strpos($source, $guard);
            $this->assertNotFalse($start, "the $guard branch is gone");

            $end = strpos($source, '}', $start);
            $branch = substr($source, $start, $end - $start);

            $this->assertStringContainsString(
                'break;',
                $branch,
                "the $guard branch assigns a message and carries on, so count() is called on a string"
            );
        }
    }

    /**
     * The two states say different things. "Deleted from Mailchimp" is final;
     * "not finished" is the common case and resolves itself, and until
     * getBatchResponse() separated them a merchant was told neither.
     *
     * @dataProvider controllerProvider
     * @param string $path
     */
    public function testTheTwoStatesAreNotCollapsedIntoOneMessage($path)
    {
        $source = file_get_contents($path);

        $this->assertStringContainsString('Response was deleted from MailChimp servers', $source);
        $this->assertStringContainsString('has not finished on Mailchimp yet', $source);
    }
}
