<?php
namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class Migrate24 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $table = $this->moduleDataSetup->getTable('sales_order');
        $select = $this->moduleDataSetup->getConnection()->select()
            ->from(
            false,
            ['mailchimp_flag' => new \Zend_Db_Expr('IF(mailchimp_abandonedcart_flag OR mailchimp_campaign_id OR mailchimp_landing_page, 1, 0)')]
        )->join(['O'=>$table], 'O.entity_id = G.entity_id', []);
        $query = $this->moduleDataSetup->getConnection()->updateFromSelect($select, ['G' => $this->moduleDataSetup->getTable('sales_order_grid')]);

        $this->moduleDataSetup->getConnection()->query($query);

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
        return '1.0.24';
    }
}

