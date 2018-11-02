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
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\DeploymentConfig;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_serializer;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory
     */
    protected $_insterestGroupCollectionFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory
     */
    protected $_webhookCollectionFactory;

    /**
     * UpgradeData constructor.
     * @param ResourceConnection $resource
     * @param DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory $interestGroupCollectionFactory
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollectionFactory
     */
    public function __construct(
        ResourceConnection $resource,
        DeploymentConfig $deploymentConfig,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory $interestGroupCollectionFactory,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollectionFactory
    )
    {
        $this->_resource            = $resource;
        $this->_deploymentConfig    = $deploymentConfig;
        $this->_helper              = $helper;
        $this->_serializer          = $serializer;
        $this->_storeManager        = $storeManager;
        $this->_scopeConfig         = $scopeConfig;
        $this->_insterestGroupCollectionFactory = $interestGroupCollectionFactory;
        $this->_webhookCollectionFactory        = $webhookCollectionFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.24') < 0)
        {
            $setup->startSetup();
            $connection = $this->_resource->getConnectionByName('default');
            if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/sales')) {
                    $salesConnection = $this->_resource->getConnectionByName('sales');
            }
            else {
                    $salesConnection = $connection;
            }
            $table = $setup->getTable('sales_order');
            $select = $salesConnection->select()
                ->from(
                    false,
                    ['mailchimp_flag' => new \Zend_Db_Expr('IF(mailchimp_abandonedcart_flag OR mailchimp_campaign_id OR mailchimp_landing_page, 1, 0)')]
                )->join(['O'=>$table],'O.entity_id = G.entity_id',[]);

            $query = $salesConnection->updateFromSelect($select, ['G' => $setup->getTable('sales_order_grid')]);

            $salesConnection->query($query);
            $setup->endSetup();

        }
        if (version_compare($context->getVersion(), '1.0.31') < 0)
        {
            // must convert all the serialized data in the db
            // convert the data in core_config_data
            $setup->startSetup();
            $connection = $this->_resource->getConnectionByName('default');
            $table = $setup->getTable('core_config_data');
            $select = $connection->select()->from($table)->where('path = ?',\Ebizmarts\MailChimp\Helper\Data::XML_MERGEVARS);
            $rows = $connection->fetchAll($select);
            foreach ($rows as $row) {
                $value = $row['value'];
                $uvalue = unserialize($value);
                $row['value'] = $this->_serializer->serialize($uvalue);
                $where = ['config_id =?'=> $row['config_id']];
                $connection->update($table,$row,$where);
            }

            // convert table mailchimp_interest_group
            /**
             * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup $item
             */
            $lastId = 0;
            $done = false;
            while(!$done) {
                $collection = $this->_insterestGroupCollectionFactory->create();
                $collection->addFieldToFilter('id',['gt' => $lastId]);
                $collection->getSelect()->limit(500);
                if (!$collection->getSize()) {
                    $done = true;
                } else {
                    foreach ($collection as $item) {
                        $group = $item->getGroupdata();
                        $ugroup = unserialize($group);
                        $item->setGroupdata($this->_serializer->serialize($ugroup));
                        $item->getResource()->save($item);
                        $lastId = $item->getId();
                    }
                }
            }
            // convert table mailchimp_webhook_request
            /**
             * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest $webhookItem
             */
            $lastId = 0;
            $done = false;
            while(!$done) {
                $webhookCollection = $this->_webhookCollectionFactory->create();
                $webhookCollection->addFieldToFilter('processed', ['neq' => 1]);
                $webhookCollection->addFieldToFilter('id',['gt' => $lastId]);
                $webhookCollection->getSelect()->limit(500);
                if (!$webhookCollection->getSize()) {
                    $done = true;
                } else {
                    foreach ($webhookCollection as $webhookItem) {
                        $dt = $webhookItem->getDataRequest();
                        $udt = unserialize($dt);
                        $webhookItem->setDataRequest($this->_serializer->serialize($udt));
                        $webhookItem->getResource()->save($webhookItem);
                        $lastId = $item->getId();
                    }
                }
            }
        }
    }
}