<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Getaccountdetails extends Action
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * @param Context $context
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
    }

    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $param['apikey'];
        $store = $param['store'];
        $encrypt = $param['encrypt'];
        try {
            if ($encrypt == 3) {
                $api = $this->_helper->getApi($this->_storeManager->getStore()->getId());
            } else {
                $api = $this->_helper->getApiByApiKey($apiKey, $encrypt);
            }
            $apiInfo = $api->root->info();
            $options = [];
            if (isset($apiInfo['account_name'])) {
                $options['username'] = ['label' => __('User name:'), 'value' => $apiInfo['account_name']];
                $options['total_subscribers'] = [
                    'label' => __('Total Account Subscribers:'),
                    'value' => $apiInfo['total_subscribers']
                ];
                if ($store != -1) {
                    $storeData = $api->ecommerce->stores->get($store);
                    $options['list_id'] = $storeData['list_id'];
                    $list = $api->lists->getLists($storeData['list_id']);
                    $options['list_name'] = $list['name'];
                    $options['total_list_subscribers'] = [
                        'label' => __('Total List Subscribers:'),
                        'value' => $list['stats']['member_count']
                    ];
                    $options['subtitle'] = ['label' => __('Ecommerce Data uploaded to MailChimp:'), 'value' => ''];
                    $totalCustomers = $api->ecommerce->customers->getAll($store, 'total_items');
                    $options['total_customers'] = [
                        'label' => __('Total customers:'),
                        'value' => $totalCustomers['total_items']
                    ];
                    $totalProducts = $api->ecommerce->products->getAll($store, 'total_items');
                    $options['total_products'] = [
                        'label' => __('Total products:'),
                        'value' => $totalProducts['total_items']
                    ];
                    $totalOrders = $api->ecommerce->orders->getAll($store, 'total_items');
                    $options['total_orders'] = ['label' => __('Total orders:'), 'value' => $totalOrders['total_items']];
                    $totalCarts = $api->ecommerce->carts->getAll($store, 'total_items');
                    $options['total_carts'] = ['label' => __('Total Carts:'), 'value' => $totalCarts['total_items']];
                    $options['notsaved'] = [
                        'label' => __('Ecommerce disabled, save configuration to enable'),
                        'value' => ''
                    ];
                } else {
                    $options['nostore'] = [
                        'label' => __(
                            'Ecommerce disabled, only subscribers will be synchronized (your orders, products,etc will be not synchronized)'
                        ),
                        'value' => ''
                    ];
                }
            }
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
            $options['error'] = ['label' => 'Error', 'value' => __('--- Invalid API Key ---')];
        }

        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($options);

        return $resultJson;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }
}
