<?php
namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Ebizmarts\MailChimp\Helper\Data;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory;

class Migrate32 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var Data
     */
    private $helper;
    private $webhookCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Data $helper,
        CollectionFactory $webhookCollectionFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->helper = $helper;
        $this->webhookCollectionFactory = $webhookCollectionFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        // delete the old serialized data from core_config_data
        $table = $this->moduleDataSetup->getTable('core_config_data');
        try {
            $this->moduleDataSetup->getConnection()->delete($table, ['path = ?'=> \Ebizmarts\MailChimp\Helper\Data::XML_MERGEVARS]);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
        // empty table mailchimp_interest_group
        $table = $this->moduleDataSetup->getTable('mailchimp_interest_group');
        try {
            $this->moduleDataSetup->getConnection()->delete($table);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
        // convert table mailchimp_webhook_request
        $lastId = 0;
        $done = false;
        while (!$done) {
            $webhookCollection = $this->webhookCollectionFactory->create();
            $webhookCollection->addFieldToFilter('processed', ['neq' => 1]);
            $webhookCollection->addFieldToFilter('id', ['gt' => $lastId]);
            $webhookCollection->getSelect()->limit(500);
            if (!$webhookCollection->getSize()) {
                $done = true;
            } else {
                foreach ($webhookCollection as $webhookItem) {
                    try {
                        $webhookItem->setProcessed(\Ebizmarts\MailChimp\Cron\Webhook::DATA_NOT_CONVERTED);
                        $webhookItem->getResource()->save($webhookItem);
                    } catch (\Exception $e) {
                        $this->helper->log($e->getMessage());
                        $webhookItem->setProcesed(\Ebizmarts\MailChimp\Cron\Webhook::DATA_WITH_ERROR);
                        $webhookItem->getResource()->save($webhookItem);
                    }
                    $lastId = $webhookItem->getId();
                }
            }
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
        return '1.2.32';
    }
}

