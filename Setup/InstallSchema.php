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

class InstallSchema implements InstallSchemaInterface
{
    private $_helper;

    public function __construct(\Ebizmarts\MailChimp\Helper\Data $helper)
    {
        $this->_helper = $helper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $connection = $installer->getConnection();
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mailchimp_sync_batches'))
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

        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
            ->newTable($installer->getTable('mailchimp_errors'))
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

        $installer->getConnection()->createTable($table);


        $table = $installer->getConnection()
            ->newTable($installer->getTable('mailchimp_sync_ecommerce'))
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

        $installer->getConnection()->createTable($table);



//        $connection->addColumn(
//            $installer->getTable('newsletter_subscriber'),
//            'mailchimp_id',
//            [
//                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
//                'default' => '',
//                'comment' => 'Mailchimp reference'
//            ]
//        );

        $connection->addColumn(
            $installer->getTable('quote'),
            'mailchimp_abandonedcart_flag',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Retrieved from Mailchimp'
            ]
        );

        $connection->addColumn(
            $installer->getTable('sales_order'),
            'mailchimp_abandonedcart_flag',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Retrieved from Mailchimp'
            ]
        );

        $path = $this->_helper->getBaseDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'Mailchimp';
        try {
            if (!is_dir($path)) {
                mkdir($path);
            }
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        $installer->endSetup();
    }
}
