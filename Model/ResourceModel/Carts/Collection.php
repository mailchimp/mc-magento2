<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\Carts;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'mailchimp_sync_ecommerce',
        $resourceModel = \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->where(
                "type = 'QUO'"
            );
        $this->getSelect()->join(['alias' => $this->getTable('quote')], 'main_table.related_id = alias.entity_id',
            ['customer_id', 'customer_email','customer_firstname','customer_lastname','items_count','items_qty','created_at','updated_at','grand_total']);
        return $this;

    }

}
