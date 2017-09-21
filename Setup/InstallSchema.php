<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/7/16 3:36 PM
 * @file: InstallSchema.php
 */
namespace Ebizmarts\MailChimp\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\DeploymentConfig;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;
    public function __construct(ResourceConnection $resource,DeploymentConfig $deploymentConfig)
    {
        $this->_resource = $resource;
        $this->_deploymentConfig = $deploymentConfig;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $connection = $this->_resource->getConnectionByName('default');
        $table = $connection
            ->newTable($connection->getTableName('mailchimp_sync_batches'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Batch Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->addColumn(
                'mailchimp_store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->addColumn(
                'batch_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                24,
                [],
                'Batch Id'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                10,
                [],
                'Status'
            );

        $connection->createTable($table);

        $table = $connection
            ->newTable($connection->getTableName('mailchimp_errors'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Batch Id'
            )
            ->addColumn(
                'mailchimp_store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                [],
                'type'
            )
            ->addColumn(
                'title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                [],
                'title'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'status'
            )
            ->addColumn(
                'errors',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                [],
                'errors'
            )
            ->addColumn(
                'regtype',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                3,
                [],
                'regtype'
            );

        $connection->createTable($table);


        $table = $connection
            ->newTable($connection->getTableName('mailchimp_sync_ecommerce'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'mailchimp_store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                24,
                [],
                'Type of register'
            )
            ->addColumn(
                'related_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Id of the related entity'
            )
            ->addColumn(
                'mailchimp_sync_modified',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                [],
                'If the entity was modified'
            )
            ->addColumn(
                'mailchimp_sync_delta',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Sync Delta'
            )->addColumn(
                'mailchimp_sync_error',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                [],
                'Error on synchronization'
            )->addColumn(
                'mailchimp_sync_deleted',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                [],
                'If the object was deleted in mailchimp'
            )->addColumn(
                'mailchimp_token',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Quote token'
            );

        $connection->createTable($table);

        if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/sales')) {
            $connection = $this->_resource->getConnectionByName('sales');
        }

        $connection->addColumn(
            $connection->getTableName('sales_order'),
            'mailchimp_abandonedcart_flag',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Retrieved from Mailchimp'
            ]
        );
        if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/checkout')) {
            $connection = $this->_resource->getConnectionByName('checkout');
        }
        $connection->addColumn(
            $connection->getTableName('quote'),
            'mailchimp_abandonedcart_flag',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Retrieved from Mailchimp'
            ]
        );

        $path = BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'Mailchimp';
        if (!is_dir($path)) {
            mkdir($path);
        }
    }
}
