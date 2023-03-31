<?php

namespace Ebizmarts\MailChimp\Model\ResourceModel\Schedule;

use \Magento\Cron\Model\ResourceModel\Schedule as MagentoSchedule;
use \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
class Collection extends SearchResult
{


    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable='cron_schedule',
        $resourceModel=MagentoSchedule::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

    }//end __construct()


    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->where(
                "job_code IN (
                    'ebizmarts_webhooks',
                    'ebizmarts_ecommerce',
                    'ebizmarts_clean_webhooks',
                    'ebizmarts_clean_batches',
                    'ebizmarts_clean_errors'
                )"
            );
        return $this;

    }//end _initSelect()


}//end class
