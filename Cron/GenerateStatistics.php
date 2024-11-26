<?php

namespace Ebizmarts\MailChimp\Cron;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Store\Model\StoreManager;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;
use Ebizmarts\MailChimp\Model\MailchimpNotificationFactory as MailchimpNotificationFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Ebizmarts\MailChimp\Helper\Data;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory as MailChimpSyncBatchesCollectionFactory;
use Ebizmarts\MailChimp\Model\Config\ModuleVersion;

class GenerateStatistics
{
    /**
     * @var MailchimpNotificationFactory
     */
    protected $mailchimpNotificationFactory;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var StoreManager
     */
    protected $storeManager;
    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var MailChimpSyncBatchesCollectionFactory
     */
    protected $mailChimpSyncBatchesCollectionFactory;
    /**
     * @var ScheduleCollectionFactory
     */
    protected $scheduleCollectionFactory;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var ModuleVersion
     */
    protected $moduleVersion;
    protected $deleteAction = [
        0 => 'Unsubscribe',
        1 => 'Delete',
    ];
    public function __construct(
        MailchimpNotificationFactory $mailchimpNotificationFactory,
        Data $helper,
        StoreManager $storeManager,
        CustomerCollectionFactory $customerCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        MailchimpSyncBatchesCollectionFactory $mailchimpSyncBatchesCollectionFactory,
        ScheduleCollectionFactory $scheduleCollectionFactory,
        ProductMetadataInterface $productMetadata,
        ModuleVersion $moduleVersion
    )
    {
        $this->mailchimpNotificationFactory = $mailchimpNotificationFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->mailChimpSyncBatchesCollectionFactory = $mailchimpSyncBatchesCollectionFactory;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->productMetadata = $productMetadata;
        $this->moduleVersion = $moduleVersion;
    }
    public function execute()
    {
        $data = [];
        foreach ($this->storeManager->getStores() as $storeId => $val)
        {
            $mailChimpStoreId = $this->helper->getConfigValue(Data::XML_MAILCHIMP_STORE, $storeId);
            $storeStatistics = [];
            // Get currents mailchimp totals (orders, products, customers)
            $storeStatistics['mailchimp'] = $this->getMailchimpTotals($storeId);
            $storeStatistics['magento'] = $this->getMagentoTotals($storeId);
            $data['statistics']['store'][$storeId] = $storeStatistics;
            $data['batches'] = $this->getBatches($storeId, $mailChimpStoreId);
            $data['jobs'] = $this->getJobs();
        }
        $mailchimpNotification = $this->mailchimpNotificationFactory->create();
        $mailchimpNotification->setNotificationData(json_encode($data));
        $mailchimpNotification->setProcessed(false);
        $mailchimpNotification->getResource()->save($mailchimpNotification);
    }
    private function getMagentoTotals($storeId)
    {
        $options = [];
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addFieldToFilter('store_id', ['eq'=>$storeId]);
        $customerCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['entity_id','store_id']);
        $totalCustomers = $customerCollection->count();
        $options['total_customers'] = $totalCustomers;

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addStoreFilter($storeId);
        $productCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['entity_id']);
        $totalProducts = $productCollection->count();
        $options['total_products'] = $totalProducts;

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
        $orderCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['entity_id']);
        $totalOrders = $orderCollection->count();
        $options['total_orders'] = $totalOrders;
        $storeUrl = stripslashes($this->storeManager->getStore($storeId)->getUrl());
        $options['store_url'] = stripslashes($storeUrl);
        // get all de configuration
        $config = [];
        $config['general']['active'] = $this->helper->getConfigValue(Data::XML_PATH_ACTIVE, $storeId);
        $config['general']['store'] = $this->helper->getConfigValue(Data::XML_MAILCHIMP_STORE, $storeId);
        $config['general']['list'] = $this->helper->getConfigValue(Data::XML_PATH_LIST, $storeId);
        $config['general']['popup_form'] = $this->helper->getConfigValue(Data::XML_POPUP_FORM, $storeId);
        if ($config['general']['popup_form']) {
            $config['general']['popup_url'] = $this->helper->getConfigValue(Data::XML_POPUP_URL, $storeId);
        }
        $config['general']['magento_email'] = $this->helper->getConfigValue(Data::XML_MAGENTO_MAIL, $storeId);
        $config['general']['two-way_sync'] = $this->helper->getConfigValue(Data::XML_PATH_WEBHOOK_ACTIVE, $storeId);
        if ( $config['general']['two-way_sync']) {
            $config['general']['delete_action'] = $this->deleteAction[$this->helper->getConfigValue(Data::XML_PATH_WEBHOOK_DELETE)];
        }
        $config['general']['enable_log'] = $this->helper->getConfigValue(Data::XML_PATH_LOG,$storeId);
//        $config['general']['field_mapping'] = $this->helper->getConfigValue(Data::XML_MERGEVARS, $storeId);
        $config['general']['mapping'] = $this->helper->getMapFields($storeId, false);
        $config['general']['interest'] = $this->helper->getConfigValue(Data::XML_INTEREST, $storeId);
        $config['general']['show_groups'] = $this->helper->getConfigValue(Data::XML_INTEREST_IN_SUCCESS);
        if ($config['general']['show_groups']) {
            $config['general']['group_description'] = $this->helper->getConfigValue(Data::XML_INTEREST_SUCCESS_HTML_BEFORE, $storeId);
            $config['general']['succes_message'] = $this->helper->getConfigValue(Data::XML_INTEREST_SUCCESS_HTML_AFTER, $storeId);
        }
        $config['general']['timeout'] = $this->helper->getConfigValue(Data::XML_PATH_TIMEOUT, $storeId);
        $config['ecommerce']['enabled'] = $this->helper->getConfigValue(Data::XML_PATH_ECOMMERCE_ACTIVE, $storeId);
        $config['ecommerce']['sync_all_customers'] = $this->helper->getConfigValue(Data::XML_PATH_ALL_CUSTOMERS, $storeId);
        $config['ecommerce']['subscribe_all_customers'] = $this->helper->getConfigValue(Data::XML_ECOMMERCE_OPTIN, $storeId);
        $config['ecommerce']['first_date'] = $this->helper->getConfigValue(Data::XML_ECOMMERCE_FIRSTDATE, $storeId);
        $config['ecommerce']['send_promo'] = $this->helper->getConfigValue(Data::XML_SEND_PROMO, $storeId);
        $config['ecommerce']['include_taxes'] = $this->helper->getConfigValue(Data::XML_INCLUDING_TAXES, $storeId);
        $config['ecommerce']['campaign_attribution'] = $this->helper->getConfigValue(Data::XML_CAMPAIGN_ACTION, $storeId);
        $config['ecommerce']['monts_to_clear_error_table'] = $this->helper->getConfigValue(Data::XML_CLEAN_ERROR_MONTHS, $storeId);
        $config['ac']['enabled'] = $this->helper->getConfigValue(Data::XML_ABANDONEDCART_ACTIVE, $storeId);
        $config['ac']['first_date'] = $this->helper->getConfigValue(Data::XML_ABANDONEDCART_FIRSTDATE, $storeId);
        $config['ac']['redirect_page'] = $this->helper->getConfigValue(Data::XML_ABANDONEDCART_PAGE, $storeId);
        $config['ac']['save_email'] = $this->helper->getConfigValue(Data::XML_ABANDONEDCART_EMAIL, $storeId);
        $options['config'] = $config;
        $options['magento_version'] = $this->productMetadata->getVersion();
        $options['mc2-version'] = $this->moduleVersion->getModuleVersion('Ebizmarts_MailChimp');
        try {
            $options['lib-version'] = $this->moduleVersion->getLibVersion('ebizmarts/mailchimp-lib');
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }

        return $options;
    }
    private function getMailchimpTotals($storeId)
    {
        $mailchimpList = $this->helper->getGeneralList($storeId);
        $mailChimpStoreId = $this->helper->getConfigValue(Data::XML_MAILCHIMP_STORE, $storeId);
        $api = $this->helper->getApi($storeId);
        $apiInfo = $api->root->info();
        $options = [];
        $options ['store']  = $mailChimpStoreId;
        if (isset($apiInfo['account_name'])) {
            $options['webhooks'] = $this->getWebhooks($api, $mailchimpList);
            $options['username'] =$apiInfo['account_name'];
            $options['total_subscribers'] = $apiInfo['total_subscribers'];
            if ($storeId != -1) {
                $storeData = $api->ecommerce->stores->get($mailChimpStoreId);
                $options['list_id'] = $storeData['list_id'];
                $list = $api->lists->getLists($storeData['list_id']);
                $options['list_name'] = $list['name'];
                $options['total_list_subscribers'] = $list['stats']['member_count'];
                $totalCustomers = $api->ecommerce->customers->getAll($mailChimpStoreId, 'total_items');
                $options['total_customers'] = $totalCustomers['total_items'];
                $totalProducts = $api->ecommerce->products->getAll($mailChimpStoreId, 'total_items');
                $options['total_products'] = $totalProducts['total_items'];
                $totalOrders = $api->ecommerce->orders->getAll($mailChimpStoreId, 'total_items');
                $options['total_orders'] = $totalOrders['total_items'];
                $totalCarts = $api->ecommerce->carts->getAll($mailChimpStoreId, 'total_items');
                $options['total_carts'] = $totalCarts['total_items'];
                $options['currency_code'] = $storeData['currency_code'];
                $options['money_format'] = $storeData['money_format'];
                $options['primary_locale'] = $storeData['primary_locale'];
                $options['timezone'] = $storeData['timezone'];
                $options['is_syncing'] = $storeData['is_syncing'];
                $contact = [];
                $contact['email_address'] = $storeData['email_address'];
                $contact['phone_number'] = $storeData['phone'];
                $contact['address'] = $storeData['address'];
                $options['contact'] = $contact;
            } else {
                $options['nostore'] = ['label' => __('Ecommerce disabled, only subscribers will be synchronized (your orders, products,etc will be not synchronized)'), 'value' => ''];
            }
        }
        return $options;
    }
    private function getWebhooks($api, $listId)
    {
        $ret = [];
        $webhooks = $api->lists->webhooks->getAll($listId);
        foreach ($webhooks['webhooks'] as $webhook) {
            $item =[];
            $item['url'] = $webhook['url'];
            $item['events'] = $webhook['events'];
            $item['sources'] = $webhook['sources'];
            $ret[] = $item;
        }
        return $ret;
    }
    private function getBatches($storeId,$mailchimpStoreId)
    {
        $batches = [];
        $collection = $this->mailChimpSyncBatchesCollectionFactory->create();
        $collection
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('mailchimp_store_id', ['eq' => $mailchimpStoreId]);
        $collection->setOrder('modified_date', 'DESC');
        $collection->getSelect()->limit(10);
        foreach ($collection as $item) {
            $batch = [];
            $batch['id'] = $item['batch_id'];
            $batch['date'] = $item['modified_date'];
            $batch['status'] = $item['status'];
            $counters = [];
            $counters['carts_new'] = $item['carts_new_count'];
            $counters['order_new'] = $item['orders_new_count'];
            $counters['products_new'] = $item['products_new_count'];
            $counters['customers_new'] = $item['customers_new_count'];
            $counters['subscribers_new'] = $item['subscribers_new_count'];
            $counters['carts_modified'] = $item['carts_modified_count'];
            $counters['customers_modified'] = $item['customers_modified_count'];
            $counters['orders_modified'] = $item['orders_modified_count'];
            $counters['products_modified'] = $item['products_modified_count'];
            $counters['subscribers_modified'] = $item['subscribers_modified_count'];
            $batch['counters'] = $counters;
            $batches[] = $batch;
        }
        return $batches;
    }
    private function getJobs()
    {
        $jobs = [];
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter('job_code', ['eq'=> 'ebizmarts_ecommerce']);
        $collection->setOrder('scheduled_at', 'DESC');
        $collection->getSelect()->limit(10);
        foreach ($collection as $item) {
            $job =[];
            $job['job_code'] = $item['job_code'];
            $job['status'] = $item['status'];
            $job['messages'] = $item['messages'];
            $job['scheduled_at'] = $item['scheduled_at'];
            $job['executed_at'] = $item['executed_at'];
            $job['finished_at'] = $item['finished_at'];
            $jobs[] = $job;
        }
        $collection = $this->scheduleCollectionFactory->create();
        $collection->addFieldToFilter('job_code', ['eq'=> 'ebizmarts_webhooks']);
        $collection->setOrder('scheduled_at', 'DESC');
        $collection->getSelect()->limit(10);
        foreach ($collection as $item) {
            $job =[];
            $job['job_code'] = $item['job_code'];
            $job['status'] = $item['status'];
            $job['messages'] = $item['messages'];
            $job['scheduled_at'] = $item['scheduled_at'];
            $job['executed_at'] = $item['executed_at'];
            $job['finished_at'] = $item['finished_at'];
            $jobs[] = $job;
        }
        return $jobs;
    }
}
