<?php
namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Ebizmarts\MailChimp\Helper\Data;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigFactory;

class Migrate35 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Data $helper
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helper,
        ConfigFactory $configFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helper = $helper;
        $this->configFactory = $configFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $configCollection = $this->configFactory->create();
        $configCollection->addFieldToFilter('path', ['eq' => \Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY]);
        /**
         * @var $config \Magento\Config\Model\ResourceModel\Config
         */
        foreach ($configCollection as $config) {
            try {
                $config->setValue($this->helper->encrypt($config->getvalue()));
                $config->getResource()->save($config);
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
            }
        }
        $configCollection = $this->configFactory->create();
        $configCollection->addFieldToFilter(
            'path',
            ['eq' => \Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY_LIST]
        );
        foreach ($configCollection as $config) {
            $config->getResource()->delete($config);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }
    public static function getDependencies()
    {
        return [];
    }
    public function getAliases()
    {
        return [];
    }
    public static function getVersion()
    {
        return '102.3.35';
    }
}

