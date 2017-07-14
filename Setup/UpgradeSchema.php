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
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'mailchimp_campaign_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 16,
                    'default' => '',
                    'comment' => 'Campaign'
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'mailchimp_campaign_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 16,
                    'default' => '',
                    'comment' => 'Campaign'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'mailchimp_landing_page',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 512,
                    'default' => '',
                    'comment' => 'Landing Page'
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'mailchimp_landing_page',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 512,
                    'default' => '',
                    'comment' => 'Landing Page'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_errors'),
                'original_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 11,
                    'default' => null,
                    'comment' => 'Associated object ID'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_errors'),
                'batch_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 64,
                    'default' => null,
                    'comment' => 'Mailchimp Batch ID'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.10') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_errors'),
                'store_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 11,
                    'default' => null,
                    'comment' => 'Magento Store Id'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.11') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_stores'),
                'domain',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 512,
                    'default' => null,
                    'comment' => 'Domain'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.12') < 0) {
            $installer->getConnection()->changecolumn(
                $installer->getTable('mailchimp_stores'),
                'address_address1',
                'address_address_one',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'default' => null,
                    'comment' => 'first street address'
                ]
            );
            $installer->getConnection()->changecolumn(
                $installer->getTable('mailchimp_stores'),
                'address_address2',
                'address_address_two',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'default' => null,
                    'comment' => 'second street address'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.13') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_stores'),
                'mc_account_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 512,
                    'default' => null,
                    'comment' => 'MC account name'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_stores'),
                'list_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 512,
                    'default' => null,
                    'comment' => 'List Name'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.14') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mailchimp_sync_ecommerce'),
                'batch_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 64,
                    'default' => null,
                    'comment' => 'Mailchimp batch Id'
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.15') < 0) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mailchimp_webhook_request'))
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Id'
                )
                ->addColumn(
                    'type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['unsigned' => true, 'nullable' => false],
                    'request type'
                )
                ->addColumn(
                    'fired_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    [],
                    'date of the request'
                )
                ->addColumn(
                    'data_request',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    4096,
                    ['unsigned' => true, 'nullable' => false],
                    'data of the request'
                )
                ->addColumn(
                    'processed',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    [],
                    'Already processed'
                );
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
