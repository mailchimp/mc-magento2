<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


namespace Ebizmarts\MailChimp\Model\Config\Source;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;
use Magento\Directory\Helper\Data as DirectoryHelper;

class Details implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var null
     */
    private $_options = null;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper  = null;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_message;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $storeManager;
    /**
     * @var MailChimpHttp
     */
    private $httpClient;
    private $_error = '';

    /**
     * @param MailChimpHelper $helper
     * @param \Magento\Framework\Message\ManagerInterface $message
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param MailChimpHttp $httpClient
     * @param DirectoryHelper $directoryHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\Message\ManagerInterface $message,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        MailChimpHttp $httpClient,
        DirectoryHelper $directoryHelper
    ) {
        $this->_message = $message;
        $this->_helper  = $helper;
        $this->storeManager = $storeManager;
        $this->httpClient = $httpClient;
        $this->httpClient->setUrl($helper->getConfigValue(MailChimpHelper::XML_REGISTER_URL).'/register');
        $storeId = (int) $request->getParam("store", 0);
        if ($request->getParam('website', 0)) {
            $scope = 'website';
            $storeId = $request->getParam('website', 0);
        } elseif ($request->getParam('store', 0)) {
            $scope = 'stores';
            $storeId = $request->getParam('store', 0);
        } else {
            $scope = 'default';
        }
        if ($this->_helper->getApiKey($storeId, $scope)) {
            $api = $this->_helper->getApi($storeId, $scope);
            try {
                $this->_options = $api->root->info();
                $optionsList = $api->lists->getLists(
                    $this->_helper->getConfigValue(
                        MailChimpHelper::XML_PATH_LIST,
                        $storeId,
                        $scope
                    )
                );
                if ($optionsList &&
                    array_key_exists('stats', $optionsList) &&
                    array_key_exists('member_count', $optionsList['stats'])) {
                    $this->_options['list_subscribers'] = $optionsList['stats']['member_count'];
                }
                $mailchimpStoreId = $this->_helper->getConfigValue(
                    MailChimpHelper::XML_MAILCHIMP_STORE,
                    $storeId,
                    $scope
                );
                if ($mailchimpStoreId && $mailchimpStoreId!=-1 &&
                    $this->_helper->getConfigValue(
                        MailChimpHelper::XML_PATH_ECOMMERCE_ACTIVE,
                        $storeId,
                        $scope
                    )
                ) {
                    $token = $this->_helper->getConfigValue(MailChimpHelper::XML_STATISTICS_TOKEN, $storeId, $scope);
                    if (!$token) {
                        $registerData['store_url'] = stripslashes($this->storeManager->getStore($storeId)->getBaseUrl());
                        $registerData['account_name'] = $this->_options['account_name'];
                        $registerData['email'] = $this->_options['email'];
                        $registerData['first_name'] = $this->_options['first_name'];
                        $registerData['last_name'] = $this->_options['last_name'];
                        $registerData['pricing_plan_type'] = $this->_options['pricing_plan_type'];
                        $registerData['username'] = $this->_options['username'];
                        $registerData['account_id'] = $this->_options['account_id'];
                        $registerData['city'] = $this->_helper->getConfigValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_CITY,$storeId, $scope);
                        $registerData['country'] = $directoryHelper->getDefaultCountry($storeId);
                        $registerData['total_subscribers'] = $this->_options['total_subscribers'];
                        $registerDataJson = json_encode($registerData);
                        $ret = $this->httpClient->post($registerDataJson);
                        $ret = json_decode($ret,true);
                        if ( !$ret['error']) {
                            $error = false;
                            $token = $ret['token'];
                            $this->_helper->saveConfigValue(MailChimpHelper::XML_STATISTICS_TOKEN, $token,  $storeId, $scope);
                        }
                    }
                    $storeInfo = $api->ecommerce->stores->get($mailchimpStoreId);
                    $this->_options['is_syncing'] = $storeInfo['is_syncing'];
                    $this->_options['date_sync'] = $this->getDateSync($mailchimpStoreId);
                    $this->_options['store_exists'] = true;
                    $totalCustomers = $api->ecommerce->customers->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_customers'] = $totalCustomers['total_items'];
                    $totalProducts = $api->ecommerce->products->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_products'] = $totalProducts['total_items'];
                    $totalOrders = $api->ecommerce->orders->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_orders'] = $totalOrders['total_items'];
                    $totalCarts = $api->ecommerce->carts->getAll($mailchimpStoreId, 'total_items');
                    $this->_options['total_carts'] = $totalCarts['total_items'];
                } else {
                    $this->_options['store_exists'] = false;
                }
                $token = $this->_helper->getConfigValue(MailChimpHelper::XML_STATISTICS_TOKEN, $storeId, $scope);
                $this->_options['token'] = $token;

            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
                $this->_error = $e->getMessage();
                $this->_options['store_exists'] = false;
            }
        } else {
            $this->_options = '--- Enter your API Key ---';
        }
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $ret = '';
        if ($this->_error != '') {
            $ret = [['label' => 'Important', 'value' => __("Store not found")]];
        } elseif(is_array($this->_options)) {
            if (isset($this->_options['account_name'])) {
                $ret = [
                    ['label' => __('Username'), 'value' => $this->_options['account_name']],
                    ['label' => 'Total Member Subscribers', 'value' => $this->_options['total_subscribers']]];
                if (array_key_exists('list_subscribers', $this->_options)) {
                    $ret = array_merge(
                        $ret,
                        [['label' => 'Total List Subscribers', 'value' => $this->_options['list_subscribers']]]
                    );
                }
                $ret = array_merge($ret, [['label'=> __('Registration ID'), 'value' => $this->_options['token']]]);
                if (isset($this->_options['store_exists']) && $this->_options['store_exists']) {
                    $ret = array_merge($ret, [
                        ['label' => 'Ecommerce Data uploaded to MailChimp', 'value' => ''],
                        ['label' => '  Total Customers', 'value' => $this->_options['total_customers']],
                        ['label' => '  Total Products', 'value' => $this->_options['total_products']],
                        ['label' => '  Total Orders', 'value' => $this->_options['total_orders']],
                        ['label' => '  Total Carts', 'value' => $this->_options['total_carts']]
                    ]);
                    if ($this->_options['is_syncing']) {
                        $ret = array_merge($ret, [
                            ['label'=> __('This account is currently syncing'), 'value'=>'']
                        ]);
                    } else {
                        $ret = array_merge($ret, [
                            ['label'=> __('Account Synced since'), 'value'=>$this->_options['date_sync']]
                        ]);
                    }
                } else {
                    $ret = array_merge($ret, [
                        ['label'=>'Ecommerce disabled, only subscribers will be synchronized (your orders, products,etc will be not synchronized)','value'=>'']
                    ]);
                }
            }
        } elseif (!$this->_options) {
            $ret = [
                ['label' => 'Error', 'value' => __('--- Invalid API Key ---')]
            ];
        } else {
            $ret = [['label' => 'Important', 'value' => __($this->_options)]];
        }
        return $ret;
    }
    private function getDateSync($mailchimpStoreId)
    {
        return $this->_helper->getMCMinSyncDateFlagByMailchimpStore(
            $mailchimpStoreId,
            0,
            "default"
        );
    }
}
