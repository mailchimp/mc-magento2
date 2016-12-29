<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/31/16 5:23 PM
 * @file: UpgradeSchema.php
 */
namespace Ebizmarts\MailChimp\Setup;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        if ($context->getVersion()
            && version_compare($context->getVersion(), '0.0.3') < 0
        ) {

            $table = $connection
                ->newTable($setup->getTable('mailchimp_sync_ecommerce'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Id'
                )
                ->addColumn(
                    'store_id',
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
                )
            ;

            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}