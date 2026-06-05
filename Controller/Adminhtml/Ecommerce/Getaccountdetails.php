<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/5/17 3:40 PM
 * @file: GetAccountDetails.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use \Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

class Getaccountdetails extends Action
{
    /**
     * @var MailChimpHelper
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
     * @param MailChimpHelper $helper
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        MailChimpHelper $helper
    ) {
    
        parent::__construct($context);
        $this->_resultFactory       = $context->getResultFactory();
        $this->_helper                  = $helper;
        $this->_storeManager        = $storeManager;
    }
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $param['apikey'];
        $store  = $param['store'];
        $encrypt = $param['encrypt'];
        $scope = $param['scope'];
        $scopeId = $param['scopeId'];
        try {
            if ($encrypt == 3) {
                $api = $this->_helper->getApi($scopeId, $scope);
            } else {
                $api = $this->_helper->getApiByApiKey($apiKey, $encrypt);
            }
            $apiInfo = $api->root->info();
            $options = [];
            if (isset($apiInfo['account_name'])) {
                $options['account_name'] = ['code' => 'account_name','html' => __('Account Name'),'value' => $apiInfo['account_name']];
                $options['email'] = ['code' => 'email','html' => __('email'),'value' => $apiInfo['email']];
                $options['first_name'] = ['code'=> 'first_name','html' => __('First Name'),'value' => $apiInfo['first_name']];
                $options['last_name'] = ['code'=>'last_name','html' => __('Last Name'),'value' => $apiInfo['last_name']];
                $options['pricing_plan_type'] = ['code'=>'pricing_plan_type','html' => __('Pricing Plan'),'value' => $apiInfo['pricing_plan_type']];
                $options['username'] = ['code'=>'username','html' => __('User name:'), 'value' => $apiInfo['account_name']];
                $options['account_id'] = ['code'=> 'account_id','html' => __('Account id:'), 'value' => $apiInfo['account_id']];
                $options['total_subscribers'] = ['label' => __('Total Account Subscribers:'), 'value' => $apiInfo['total_subscribers']];
                $options['total_account_subscribers'] = ['code' => 'total_subscribers', 'html' => __('Total Account Subscribers:'), 'value' => $apiInfo['total_subscribers']];
                $token = $this->_helper->getConfigValue(MailChimpHelper::XML_STATISTICS_TOKEN, $scopeId, $scope);
                if ($store != -1) {
                    $storeData = $api->ecommerce->stores->get($store);
                    $options['list_id'] = $storeData['list_id'];
                    $list = $api->lists->getLists($storeData['list_id']);
                    $options['list_name'] = $list['name'];
                    $options['total_list_subscribers'] = ['label' => __('Total List Subscribers:'), 'value' => $list['stats']['member_count']];
                    $options['token'] = ['label' => __('Registration ID:'), 'value' => $token];
                    $options['subtitle'] = ['label' => __('Ecommerce Data uploaded to MailChimp:'), 'value' => ''];
                    $totalCustomers = $api->ecommerce->customers->getAll($store, 'total_items');
                    $options['total_customers'] = ['label' => __('Total customers:'), 'value' => $totalCustomers['total_items']];
                    $totalProducts = $api->ecommerce->products->getAll($store, 'total_items');
                    $options['total_products'] = ['label' => __('Total products:'), 'value' => $totalProducts['total_items']];
                    $totalOrders = $api->ecommerce->orders->getAll($store, 'total_items');
                    $options['total_orders'] = ['label' => __('Total orders:'), 'value' => $totalOrders['total_items']];
                    $totalCarts = $api->ecommerce->carts->getAll($store, 'total_items');
                    $options['total_carts'] = ['label' => __('Total Carts:'), 'value' => $totalCarts['total_items']];
                    $options['notsaved'] = ['label' => __('Ecommerce disabled, save configuration to enable.'), 'value' => ''];
                } else {
                    $options['nostore'] = ['label' => __('Ecommerce disabled, only subscribers will be synchronized (your orders, products,etc will be not synchronized).'), 'value' => ''];
                }
            }
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
