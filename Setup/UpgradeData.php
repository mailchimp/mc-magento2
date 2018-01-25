<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/31/16 3:28 PM
 * @file: UpgradeData.php
 */
namespace Ebizmarts\MailChimp\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\DeploymentConfig;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;

    /**
     * UpgradeData constructor.
     * @param ResourceConnection $resource
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        ResourceConnection $resource,
        DeploymentConfig $deploymentConfig
    )
    {
        $this->_resource = $resource;
        $this->_deploymentConfig = $deploymentConfig;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.24') < 0)
        {
            $setup->startSetup();
            $connection = $this->_resource->getConnectionByName('default');
            if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/sales')) {
                    $salesConnection = $this->_resource->getConnectionByName('sales');
            }
            else {
                    $salesConnection = $connection;
            }
            $table = $setup->getTable('sales_order');
            $select = $salesConnection->select()
                ->from(
                    false,
                    ['mailchimp_flag' => new \Zend_Db_Expr('IF(mailchimp_abandonedcart_flag OR mailchimp_campaign_id OR mailchimp_landing_page, 1, 0)')]
                )->join(['O'=>$table],'O.entity_id = G.entity_id',[]);

            $query = $salesConnection->updateFromSelect($select, ['G' => $setup->getTable('sales_order_grid')]);

            $salesConnection->query($query);
            $setup->endSetup();

        }
    }
}