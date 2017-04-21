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
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mailchimp_stores'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Id'
                )
                ->addColumn(
                    'apikey',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'mailchimp apikey'
                )
                ->addColumn(
                    'storeid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'mailchimp store id'
                )
                ->addColumn(
                    'list_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'mailchimp store id'
                )
                ->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    128,
                    ['unsigned' => true, 'nullable' => false],
                    'store name'
                )
                ->addColumn(
                    'platform',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store platform'
                )
                ->addColumn(
                    'is_sync',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    [],
                    'if the store is synced or not'
                )
                ->addColumn(
                    'email_address',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    128,
                    ['unsigned' => true, 'nullable' => false],
                    'email associated to store'
                )
                ->addColumn(
                    'currency_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    3,
                    ['unsigned' => true, 'nullable' => false],
                    'store currency code'
                )
                ->addColumn(
                    'money_format',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    10,
                    ['unsigned' => true, 'nullable' => false],
                    'symbol of currency'
                )
                ->addColumn(
                    'primary_locale',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    5,
                    ['unsigned' => true, 'nullable' => false],
                    'store locale'
                )
                ->addColumn(
                    'timezone',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    ['unsigned' => true, 'nullable' => false],
                    'store timezone'
                )
                ->addColumn(
                    'phone',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store phone number'
                )
                ->addColumn(
                    'address_address1',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store address1'
                )
                ->addColumn(
                    'address_address2',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store address2'
                )
                ->addColumn(
                    'address_city',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store city'
                )
                ->addColumn(
                    'address_province',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store province'
                )
                ->addColumn(
                    'address_province_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2,
                    ['unsigned' => true, 'nullable' => false],
                    'store province code'
                )
                ->addColumn(
                    'address_postal_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store postal code'
                )
                ->addColumn(
                    'address_country',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'store country name'
                )
                ->addColumn(
                    'address_country_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    2,
                    ['unsigned' => true, 'nullable' => false],
                    'store country code'
                );

            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();




    }
}