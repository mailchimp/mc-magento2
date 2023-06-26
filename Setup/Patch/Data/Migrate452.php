<?php

namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Sales\Model\OrderFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory as SyncFactory;
use Ebizmarts\MailChimp\Helper\Data;

class Migrate452 implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var SyncFactory
     */
    private $syncFactory;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SyncFactory $syncFactory
     * @param OrderFactory $orderFactory
     * @param Data $helper
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SyncFactory $syncFactory,
        OrderFactory $orderFactory,
        Data $helper
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->syncFactory = $syncFactory;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
    }
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $syncCollection = $this->syncFactory->create();
        $syncCollection->addFieldToFilter('type', ['eq'=>'ORD']);
        foreach($syncCollection as $item) {
            try {
                $order = $this->orderFactory->create()->loadByAttribute('entity_id', $item->getRelatedId());
                $order->setMailchimpSent($item->getMailchimpSent());
                $order->setMailchimpSyncError($item->getMailchimpSyncError());
                $order->save();
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
            }
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }
    public function getAliases()
    {
        return [];
    }

}
