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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\ExtensionAttributesInterfaceGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\FactoryGenerator;
use Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader;
use Magento\Framework\TestFramework\Unit\Autoloader\ProxyGenerator;

/**
 * Lets the unit suite run from a checkout of this extension alone.
 *
 * Two ways in. A bare clone with `composer install` has its own vendor
 * directory; a clone placed in app/code of a Magento project does not, and
 * borrows the project's.
 */
$autoloaders = [
    __DIR__ . '/../vendor/autoload.php',        // cloned on its own
    __DIR__ . '/../../../../../app/autoload.php', // cloned into app/code
    __DIR__ . '/../../../../../vendor/autoload.php',
];

$loaded = false;
foreach ($autoloaders as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(
        STDERR,
        "Could not find an autoloader. Run `composer install` in this directory, or place this\n"
        . "extension in app/code of a Magento project that has one.\n"
    );
    exit(1);
}

if (!defined('TESTS_TEMP_DIR')) {
    define('TESTS_TEMP_DIR', __DIR__ . '/tmp');
}

/**
 * Factories are not written by hand — Magento's DI compiler generates them, so
 * a checkout does not contain them and any test naming one cannot autoload it.
 *
 * This does NOT stub them. It registers the same generator Magento's own unit
 * suite registers, which produces the real generated class on demand, from
 * magento/framework — already a dependency of this extension. A hand-written
 * stub would drift from what the compiler produces and would quietly keep the
 * suite green while testing against something the application never uses.
 *
 * Anything else the tests reach still has to be real or explicitly doubled.
 * Only generated classes come from here.
 */
$generatedCodeAutoloader = new GeneratedClassesAutoloader(
    [
        new ExtensionAttributesGenerator(),
        new ExtensionAttributesInterfaceGenerator(),
        new FactoryGenerator(),
        new ProxyGenerator(),
    ],
    new Io(
        new File(),
        TESTS_TEMP_DIR . '/' . DirectoryList::getDefaultConfig()[DirectoryList::GENERATED_CODE][DirectoryList::PATH]
    )
);

spl_autoload_register([$generatedCodeAutoloader, 'load']);
