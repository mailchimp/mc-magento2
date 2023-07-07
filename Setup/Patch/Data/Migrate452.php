<?php

namespace Ebizmarts\MailChimp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
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
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SyncFactory $syncFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helper
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SyncFactory $syncFactory,
        OrderRepositoryInterface $orderRepository,
        Data $helper
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->syncFactory = $syncFactory;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $syncCollection = $this->syncFactory->create();
        $syncCollection->addFieldToFilter('type', ['eq'=>'ORD']);
        foreach ($syncCollection as $item) {
            $orderId = $item->getRelatedId();
            try {
                $order = $this->orderRepository->get($orderId);
                if ($order && $order->getEntityId()==$orderId) {
                    $order->setMailchimpSent($item->getMailchimpSent());
                    $order->setMailchimpSyncError($item->getMailchimpSyncError());
                    $this->orderRepository->save($order);
                } else {
                    $this->helper->log("Order with id [$orderId] not found");
                }
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage(). " for order [$orderId]");
            }
        }
        // UPDATE catalog_product_entity as A
        // INNER JOIN mailchimp_sync_ecommerce as B ON A.entity_id = B.related_id
        // SET A.mailchimp_sync_error = B.mailchimp_sync_error, A.mailchimp_sent = B.mailchimp_sent
        // WHERE B.type = 'PRO';
        try {
            $tableProducts = $this->moduleDataSetup->getTable('catalog_product_entity');
            $tableEcommerce = $this->moduleDataSetup->getTable('mailchimp_sync_ecommerce');
            $query = "UPDATE `$tableProducts` as A ";
            $query .= "INNER JOIN `$tableEcommerce` as B ON A.`entity_id` = B.`related_id` ";
            $query .= "SET A.`mailchimp_sync_error` = B.`mailchimp_sync_error`, A.`mailchimp_sent` = B.`mailchimp_sent` ";
            $query .= "WHERE B.`type` = 'PRO'";
            $this->moduleDataSetup->getConnection()->query($query);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            throw new \Exception($e->getMessage());
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
