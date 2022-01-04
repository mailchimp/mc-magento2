<?php

namespace Ebizmarts\MailChimp\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $tables = [
            'mailchimp_sync_batches',
            'mailchimp_errors',
            'mailchimp_sync_ecommerce',
            'mailchimp_stores',
            'mailchimp_webhook_request',
            'mailchimp_interest_group'
        ];
        $tablesFields = [
            'sales_order' => [
                'mailchimp_abandonedcart_flag',
                'mailchimp_campaign_id',
                'mailchimp_landing_page',
                'mailchimp_flag'
            ],
            'quote' => [
                'mailchimp_abandonedcart_flag',
                'mailchimp_campaign_id',
                'mailchimp_landing_page'
            ],
            'sales_order_grid' => [
                'mailchimp_flag'
            ]
        ];
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        foreach ($tables as $table) {
            $connection->dropTable($setup->getTable($table));
        }
        foreach($tablesFields as $table => $columnArray) {
            foreach($columnArray as $column) {
                $connection->dropColumn( $setup->getTable($table), $column);
            }
        }
        $installer->endSetup();
    }
}