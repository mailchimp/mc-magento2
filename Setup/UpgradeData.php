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
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;


class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if ($context->getVersion()
            && version_compare($context->getVersion(), '0.0.2') < 0
        ) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'mailchimp_sync_modified',
                [
                    'type' => 'int',
                    'label' => 'Mailchimp Sync Modified',
                    'input' => null,
                    'required' => false,
                    'sort_order' => 150,
                    'visible' => false,
                    'system' => false,                ]
            );
            $eavSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'mailchimp_sync_delta',
                [
                    'type' => 'datetime',
                    'label' => 'Mailchimp Sync Delta',
                    'input' => null,
                    'required' => false,
                    'sort_order' => 151,
                    'visible' => false,
                    'system' => false,                ]
            );
            $eavSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'mailchimp_sync_error',
                [
                    'type' => 'varchar',
                    'label' => 'Mailchimp Sync Error',
                    'input' => null,
                    'required' => false,
                    'sort_order' => 152,
                    'visible' => false,
                    'system' => false,                ]
            );

        }
        $setup->endSetup();
    }
}