<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Composer\InstalledVersions;

class ModuleVersion
{
    const COMPOSER_FILE_NAME = 'composer.json';
    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;
    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * ModuleVersion constructor.
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     */
    public function __construct(ComponentRegistrarInterface $componentRegistrar, ReadFactory $readFactory)
    {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
    }
    public function getLibVersion($libName)
    {
        $emptyVersionNumber = '';
        try {
            $lib = InstalledVersions::getVersion($libName);
            return $lib;
        } catch (\Exception $e) {
            return $emptyVersionNumber;
        }
    }
    public function getModuleVersion($moduleName) : string
    {
        $emptyVersionNumber = '';
        $composerJsonData = null;
        try {
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
            $directoryRead = $this->readFactory->create($path);
            $composerJsonData = $directoryRead->readFile(self::COMPOSER_FILE_NAME);
        } catch (\LogicException $pathException) {
            return $emptyVersionNumber;
        } catch (FileSystemException $fsException) {
            return $emptyVersionNumber;
        } catch (\Exception $exception) {
            return $emptyVersionNumber;
        }
        try {
            $jsonData = json_decode($composerJsonData);
            if ($jsonData === null) {
                return $emptyVersionNumber;
            }
            return $jsonData->version ?? $emptyVersionNumber;
        } catch (\Exception $exception) {
            return $emptyVersionNumber;
        }
    }
}
