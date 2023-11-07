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
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Customer\Model\Customer;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory as SyncCollectionFactory;

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
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory
     */
    protected $_insterestGroupCollectionFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory
     */
    protected $_webhookCollectionFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    protected $configFactory;
    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;
    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param ResourceConnection $resource
     * @param DeploymentConfig $deploymentConfig
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory $interestGroupCollectionFactory
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollectionFactory
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        ResourceConnection $resource,
        DeploymentConfig $deploymentConfig,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup\CollectionFactory $interestGroupCollectionFactory,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpWebhookRequest\CollectionFactory $webhookCollectionFactory,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configFactory,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        $this->_resource            = $resource;
        $this->_deploymentConfig    = $deploymentConfig;
        $this->_insterestGroupCollectionFactory = $interestGroupCollectionFactory;
        $this->_webhookCollectionFactory        = $webhookCollectionFactory;
        $this->configFactory                    = $configFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->_helper              = $helper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.24') < 0) {
            $setup->startSetup();
            $connection = $this->_resource->getConnectionByName('default');
            if ($this->_deploymentConfig->get(\Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/sales')) {
                    $salesConnection = $this->_resource->getConnectionByName('sales');
            } else {
                    $salesConnection = $connection;
            }
            $table = $setup->getTable('sales_order');
            $select = $salesConnection->select()
                ->from(
                    false,
                    ['mailchimp_flag' => new \Zend_Db_Expr('IF(mailchimp_abandonedcart_flag OR mailchimp_campaign_id OR mailchimp_landing_page, 1, 0)')]
                )->join(['O'=>$table], 'O.entity_id = G.entity_id', []);

            $query = $salesConnection->updateFromSelect($select, ['G' => $setup->getTable('sales_order_grid')]);

            $salesConnection->query($query);
            $setup->endSetup();
        }
        if (version_compare($context->getVersion(), '1.1.32') < 0) {
        // must convert all the serialized data in the db
            // convert the data in core_config_data
            $setup->startSetup();
            $connection = $this->_resource->getConnectionByName('default');
            $table = $setup->getTable('core_config_data');
            $select = $connection->select()->from($table)->where('path = ?', \Ebizmarts\MailChimp\Helper\Data::XML_MERGEVARS);
            $rows = $connection->fetchAll($select);
            foreach ($rows as $row) {
                try {
                    $value = $row['value'];
                    $uvalue = unserialize($value);
                    $row['value'] = $this->_helper->serialize($uvalue);
                    $where = ['config_id =?' => $row['config_id']];
                    $connection->update($table, $row, $where);
                } catch (\Exception $e) {
                    $this->_helper->log($e->getMessage());
                    $row['value'] ='';
                    $where = ['config_id =?' => $row['config_id']];
                    $connection->update($table, $row, $where);
                }
            }

            // convert table mailchimp_interest_group
            /**
             * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup $item
             */
            $lastId = 0;
            $done = false;
            while (!$done) {
                $collection = $this->_insterestGroupCollectionFactory->create();
                $collection->addFieldToFilter('id', ['gt' => $lastId]);
                $collection->getSelect()->limit(500);
                if (!$collection->getSize()) {
                    $done = true;
                } else {
                    foreach ($collection as $item) {
                        try {
                            $group = $item->getGroupdata();
                            $ugroup = unserialize($group);
                            $item->setGroupdata($this->_helper->serialize($ugroup));
                            $item->getResource()->save($item);
                        } catch (\Exception $e) {
                            $this->_helper->log($e->getMessage());
                            $item->getResource()->delete($item);
                        }
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
            while (!$done) {
                $webhookCollection = $this->_webhookCollectionFactory->create();
                $webhookCollection->addFieldToFilter('processed', ['neq' => 1]);
                $webhookCollection->addFieldToFilter('id', ['gt' => $lastId]);
                $webhookCollection->getSelect()->limit(500);
                if (!$webhookCollection->getSize()) {
                    $done = true;
                } else {
                    foreach ($webhookCollection as $webhookItem) {
                        try {
                            $dt = $webhookItem->getDataRequest();
                            $udt = unserialize($dt);
                            $webhookItem->setDataRequest($this->_helper->serialize($udt));
                            $webhookItem->getResource()->save($webhookItem);
                        } catch (\Exception $e) {
                            $this->_helper->log($e->getMessage());
                            $webhookItem->setProcesed(\Ebizmarts\MailChimp\Cron\Webhook::DATA_WITH_ERROR);
                            $webhookItem->getResource()->save($webhookItem);
                        }
                        $lastId = $webhookItem->getId();
                    }
                }
            }
        }
        if (version_compare($context->getVersion(), '100.1.35') < 0) {
            $configCollection = $this->configFactory->create();
            $configCollection->addFieldToFilter('path', ['eq' => \Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY]);
            /**
             * @var $config \Magento\Config\Model\ResourceModel\Config
             */
            foreach($configCollection as $config) {
                try {
                    $config->setValue($this->_helper->encrypt($config->getvalue()));
                    $config->getResource()->save($config);
                } catch(\Exception $e) {
                    $this->_helper->log($e->getMessage());
                }
            }
            $configCollection = $this->configFactory->create();
            $configCollection->addFieldToFilter('path', ['eq' => \Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY_LIST]);
            foreach($configCollection as $config) {
                $config->getResource()->delete($config);
            }
        }
        if (version_compare($context->getVersion(), '100.1.58')){
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();
            /** @var $attributeSet AttributeSet */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
            $customerSetup->addAttribute(Customer::ENTITY, 'mobile_phone', [
                'type' => 'varchar',
                'label' => 'Mobile Phone',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'position' => 999,
                'system' => 0,
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'mobile_phone')
                ->addData([
                    'attribute_set_id' => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms' => ['adminhtml_customer', 'customer_account_edit'],
                ]);
            $attribute->save();
        }
    }
}
