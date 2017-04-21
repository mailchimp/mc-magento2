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

namespace Ebizmarts\MailChimp\Helper;

use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\Store;
use Symfony\Component\Config\Definition\Exception\Exception;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACTIVE            = 'mailchimp/general/active';
    const XML_PATH_APIKEY            = 'mailchimp/general/apikey';
    const XML_PATH_APIKEY_LIST       = 'mailchimp/general/apikeylist';
    const XML_PATH_MAXLISTAMOUNT     = 'mailchimp/general/maxlistamount';
    const XML_PATH_LIST              = 'mailchimp/general/list';
    const XML_PATH_LOG               = 'mailchimp/general/log';
    const XML_PATH_MAPPING           = 'mailchimp/general/mapping';
    const XML_MAILCHIMP_STORE        = 'mailchimp/general/monkeystore';
    const XML_PATH_CONFIRMATION_FLAG = 'newsletter/subscription/confirm';
    const XML_PATH_STORE             = 'mailchimp/ecommerce/store';
    const XML_PATH_ECOMMERCE_ACTIVE  = 'mailchimp/ecommerce/active';
    const XML_PATH_SYNC_DATE         = 'mailchimp/general/mcminsyncdateflag';
    const XML_ECOMMERCE_OPTIN        = 'mailchimp/ecommerce/customer_optin';
    const XML_ECOMMERCE_FIRSTDATE    = 'mailchimp/ecommerce/firstdate';
    const XML_ABANDONEDCART_ACTIVE   = 'mailchimp/abandonedcart/active';
    const XML_ABANDONEDCART_FIRSTDATE   = 'mailchimp/abandonedcart/firstdate';
    const XML_ABANDONEDCART_PAGE     = 'mailchimp/abandonedcart/page';


    const ORDER_STATE_OK             = 'complete';

    const MERGE_VARS                 = array(0 => array('magento' => 'fname', 'mailchimp' => 'FNAME'), 1 => array('magento' => 'lname', 'mailchimp' => 'LNAME'), 2 => array('magento' => 'gender', 'mailchimp' => 'GENDER'), 3 => array('magento' => 'dob', 'mailchimp' => 'DOB'), 4 => array('magento' => 'billing_address', 'mailchimp' => 'BILLING'), 5 => array('magento' => 'shipping_address', 'mailchimp' => 'SHIPPING'), 6 => array('magento' => 'billing_telephone', 'mailchimp' => 'BTELEPHONE'), 7 => array('magento' => 'shipping_telephone', 'mailchimp' => 'STELEPHONE'), 8 => array('magento' => 'billing_company', 'mailchimp' => 'BCOMPANY'), 9 => array('magento' => 'shipping_company', 'mailchimp' => 'SCOMPANY'), 10 => array('magento' => 'group_id', 'mailchimp' => 'CGROUP'), 11 => array('magento' => 'store_id', 'mailchimp' => 'STOREID'));
    const GUEST_GROUP                = 'NOT LOGGED IN';
    const IS_CUSTOMER   = "CUS";
    const IS_PRODUCT    = "PRO";
    const IS_ORDER      = "ORD";
    const IS_QUOTE      = "QUO";
    const IS_SUBSCRIBER = "SUB";

    const PLATFORM      = 'Magento2';
    const MAXSTORES     = 100;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Model\Logger\Logger
     */
    private $_mlogger;
    /**
     * @var \Magento\Customer\Model\GroupRegistry
     */
    private $_groupRegistry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Framework\App\State
     */
    private $_state;
    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    private $_loader;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $_config;
    /**
     * @var \Mailchimp
     */
    private $_api;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CustomerRepository
     */
    private $_customer;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrors
     */
    private $_mailChimpErrors;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory
     */
    private $_mailChimpSyncEcommerce;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    private $_mailChimpSyncE;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
     */
    private $_syncBatches;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpStoresFactory
     */
    private $_mailChimpStoresFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpStores
     */
    private $_mailChimpStores;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Model\Logger\Logger $logger
     * @param \Magento\Customer\Model\GroupRegistry $groupRegistry
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Mailchimp $api
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $mailChimpErrors
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $mailChimpSyncEcommerce
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $mailChimpSyncE
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Model\Logger\Logger $logger,
        \Magento\Customer\Model\GroupRegistry $groupRegistry,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Mailchimp $api,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customer,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $mailChimpErrors,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $mailChimpSyncEcommerce,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $mailChimpSyncE,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory,
        \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores
    ) {
    
        $this->_storeManager  = $storeManager;
        $this->_mlogger       = $logger;
        $this->_groupRegistry = $groupRegistry;
        $this->_scopeConfig   = $context->getScopeConfig();
        $this->_request       = $context->getRequest();
        $this->_state         = $state;
        $this->_loader        = $loader;
        $this->_config        = $config;
        $this->_api           = $api;
        $this->_customer      = $customer;
        $this->_mailChimpErrors         = $mailChimpErrors;
        $this->_mailChimpSyncEcommerce  = $mailChimpSyncEcommerce;
        $this->_mailChimpSyncE          = $mailChimpSyncE;
        $this->_syncBatches             = $syncBatches;
        $this->_mailChimpStores         = $mailChimpStores;
        $this->_mailChimpStoresFactory  = $mailChimpStoresFactory;
        parent::__construct($context);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isMailChimpEnabled($store = null)
    {
        return $this->getConfigValue(self::XML_PATH_ACTIVE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function isDoubleOptInEnabled($store = null)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIRMATION_FLAG, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getApiKey($store = null)
    {
        return $this->getConfigValue(self::XML_PATH_APIKEY, $store);
    }

    /**
     * @param null $store
     * @return \Mailchimp
     */
    public function getApi($store = null)
    {
        $this->_api->setApiKey($this->getApiKey($store));
        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        return $this->_api;
    }

    /**
     * @param $apiKey
     * @return \Mailchimp
     */
    public function getApiByApiKey($apiKey)
    {
        $this->_api->setApiKey($apiKey);
        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        return $this->_api;
    }

    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfigValue($path, $storeId = null, $scope = null)
    {
        $areaCode = $this->_state->getAreaCode();
        switch ($scope) {
            case 'website':
                $value = $this->_scopeConfig->getValue($path ,\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $storeId);
                break;
            default:
                $value = $this->_scopeConfig->getValue($path ,\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                break;
        }
        return $value;


//        if ($storeId !== null) {
//            $configValue = $this->scopeConfig->getValue(
//                $path,
//                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
//                $storeId
//            );
//        } elseif ($areaCode == 'frontend') {
//            $frontStoreId = $this->_storeManager->getStore()->getId();
//            $configValue = $this->scopeConfig->getValue(
//                $path,
//                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
//                $frontStoreId
//            );
//        } else {
//            $storeId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//            $websiteId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
//            if (!empty($storeId)) {
//                $configValue = $this->scopeConfig->getValue(
//                    $path,
//                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
//                    $storeId
//                );
//            } elseif (!empty($websiteId)) {
//                $configValue = $this->scopeConfig->getValue(
//                    $path,
//                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
//                    $websiteId
//                );
//            } else {
//                $configValue = $this->scopeConfig->getValue(
//                    $path,
//                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
//                    0
//                );
//            }
//        }
//        return $configValue;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getDefaultList($store = null)
    {
        return $this->getConfigValue(self::XML_PATH_LIST, $store);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @param $message
     * @param null $store
     */
    public function log($message, $store = null, $file = null)
    {
        if ($this->getConfigValue(self::XML_PATH_LOG, $store)) {
            $this->_mlogger->mailchimpLog($message, $file);
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getModuleVersion()
    {
        $modules = $this->_loader->load();
        $v = "";
        if (isset($modules['Ebizmarts_MailChimp'])) {
            $v = $modules['Ebizmarts_MailChimp']['setup_version'];
        }
        return $v;
    }
    public function deleteStore($mailchimpStore)
    {
        try {
//            $storeId = $this->getConfigValue(self::XML_MAILCHIMP_STORE);
            $this->getApi()->ecommerce->stores->delete($mailchimpStore);
            $this->markAllBatchesAs($mailchimpStore, 'canceled');
            $connection = $this->_syncBatches->getResource()->getConnection();
            $tableName = $this->_syncBatches->getResource()->getMainTable();
            $connection->update($tableName, ['status' => 'canceled'], "mailchimp_store_id = '".$mailchimpStore."'");
        } catch (Exception $e)
        {

        }
    }
    public function markAllBatchesAs($mailchimpStore, $status)
    {
        $connection = $this->_syncBatches->getResource()->getConnection();
        $tableName = $this->_syncBatches->getResource()->getMainTable();
        $connection->update($tableName, ['status' => $status], "mailchimp_store_id = '".$mailchimpStore."'");
    }
    public function getMCStoreName($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getFrontendName();
    }
    public function createStore($listId=null, $storeId)
    {
        if ($listId) {
            //generate store id
            $date = date('Y-m-d-His');
            $baseUrl = $this->_storeManager->getStore($storeId)->getName();
            $mailchimpStoreId = md5(parse_url($baseUrl, PHP_URL_HOST) . '_' . $date);
            $currencyCode = $this->_storeManager->getStore($storeId)->getDefaultCurrencyCode();
            $name = $this->getMCStoreName($storeId);

            //create store in mailchimp
            try {
                $this->getApi()->ecommerce->stores->add($mailchimpStoreId,$listId,$name,$currencyCode,'Magento');
                return $mailchimpStoreId;

            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }
    public function getMCMinSyncDateFlag()
    {
        return $this->getConfigValue(self::XML_PATH_SYNC_DATE);
    }
    public function getBaseDir()
    {
        return BP;
    }

    public function getMergeVars($object, $email)
    {
        $merge_vars = [];
        $mergeVars  = $this::MERGE_VARS;

        if (!$mergeVars) {
            return $merge_vars;
        }
        $customer = null;
        try {
            $customer = $this->_customer->get($email);
        } catch (\Exception $e) {
            //Customer doesn't exist. Continue with the subscriber.
        }
        foreach ($mergeVars as $map) {
            if ($customer) {
                $merge_vars = $this->_getCustomerMergeVarsValues($map, $customer, $merge_vars);
            } else {
                $merge_vars = $this->_getSubscriberMergeVarsValues($map, $object, $merge_vars);
                
            }
        }
        return $merge_vars;
    }

    protected function _getCustomerMergeVarsValues($map, $customer, $merge_vars)
    {
        $customAtt = $map['magento'];
        $chimpTag  = $map['mailchimp'];
        if ($chimpTag && $customAtt) {
            $key = strtoupper($chimpTag);
            switch ($customAtt) {
                case 'fname':
                    $val = $customer->getFirstname();
                    $merge_vars[$key] = $val;
                    break;
                case 'lname':
                    $val = $customer->getLastname();
                    $merge_vars[$key] = $val;
                    break;
                case 'gender':
                    $val = (int)$customer->getGender();
                    if ($val == 1) {
                        $merge_vars[$key] = 'Male';
                    } elseif ($val == 2) {
                        $merge_vars[$key] = 'Female';
                    }
                    break;
                case 'dob':
                    $dob = $customer->getDob();
                    if ($dob) {
                        $merge_vars[$key] = (substr($dob, 5, 2) . '/' . substr($dob, 8, 2));
                    }
                    break;
                case 'billing_address':
                case 'shipping_address':
                    $addr = explode('_', $customAtt);
                    $merge_vars = array_merge($merge_vars, $this->_updateMergeVars($key, ucfirst($addr[0]), $customer));
                    break;
                case 'billing_telephone':
                    if ($address = $customer->{'getDefaultBilling'}()) {
                        $telephone = $address->getTelephone();
                        if ($telephone) {
                            $merge_vars[$key] = $telephone;
                        }
                    }
                    break;
                case 'billing_company':
                    if ($address = $customer->{'getDefaultBilling'}()) {
                        $company = $address->getCompany();
                        if ($company) {
                            $merge_vars[$key] = $company;
                        }
                    }
                    break;
                case 'shipping_telephone':
                    if ($address = $customer->{'getDefaultShipping'}()) {
                        $telephone = $address->getTelephone();
                        if ($telephone) {
                            $merge_vars[$key] = $telephone;
                        }
                    }
                    break;
                case 'shipping_company':
                    if ($address = $customer->{'getDefaultShipping'}()) {
                        $company = $address->getCompany();
                        if ($company) {
                            $merge_vars[$key] = $company;
                        }
                    }
                    break;
                case 'group_id':
                    $merge_vars = array_merge($merge_vars, $this->_getCustomerGroup($customer, $key, $merge_vars));
                    break;
                case 'store_id':
                    $merge_vars[$key] = $customer->getStoreId();
                    break;
            }
            return $merge_vars;
        }
    }

    protected function _getSubscriberMergeVarsValues($map, $subscriber, $merge_vars)
    {
        $customAtt = $map['magento'];
        $chimpTag  = $map['mailchimp'];
        if ($chimpTag && $customAtt) {
            $key = strtoupper($chimpTag);
            switch ($customAtt) {
                case 'group_id':
                    $merge_vars = $this->_getCustomerGroup($subscriber, $key, $merge_vars);
                    break;
                case 'store_id':
                    $merge_vars[$key] = $subscriber->getStoreId();
                    break;
            }
            return $merge_vars;
        }
    }

    protected function _getCustomerGroup($customer, $key, $merge_vars)
    {
        $group_id = (int) $customer->getGroupId();
        if ($group_id == 0) {
            $merge_vars[$key] = $this::GUEST_GROUP;
        } else {
            try {
                $customerGroup = $this->_groupRegistry->retrieve($group_id);
                $merge_vars[$key] = $customerGroup->getCode();
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return $merge_vars;
    }
    protected function _updateMergeVars($key, $type, $customer)
    {
        $merge_vars = [];
        if ($address = $customer->{'getDefault' . $type}()) {
            $merge_vars[$key] = [
                'addr1' => $address->getStreetLine(1),
                'addr2' => $address->getStreetLine(2),
                'city' => $address->getCity(),
                'state' => (!$address->getRegion() ? $address->getCity() : $address->getRegion()),
                'zip' => $address->getPostcode(),
                'country' => $address->getCountryId()
            ];
        }
        return $merge_vars;
    }
    public function getDateMicrotime()
    {
        $microtime = explode(' ', microtime());
        $msec = $microtime[0];
        $msecArray = explode('.', $msec);
        $date = date('Y-m-d-H-i-s') . '-' . $msecArray[1];
        return $date;
    }
    public function resetErrors($mailchimpStore)
    {
        try {
            // clean the errors table
            $connection = $this->_mailChimpErrors->getResource()->getConnection();
            $tableName = $this->_mailChimpErrors->getResource()->getMainTable();
            $connection->delete($tableName, "mailchimp_store_id = '".$mailchimpStore."'");
//            $connection->truncateTable($tableName);
            // clean the syncecommerce table with errors
            $connection = $this->_mailChimpSyncE->getResource()->getConnection();
            $tableName = $this->_mailChimpSyncE->getResource()->getMainTable();
            $connection->delete($tableName, "mailchimp_store_id = '".$mailchimpStore."' and mailchimp_sync_error is not null");
//            $connection->truncateTable($tableName);
        } catch(\Zend_Db_Exception $e) {
            throw new ValidatorException(__($e->getMessage()));
        }
    }
    public function resetEcommerce()
    {
        $this->resetErrors();
    }
    public function saveEcommerceData($storeId, $entityId , $date, $error, $modified, $type, $deleted = 0, $token = null)
    {
        $chimpSyncEcommerce = $this->getChimpSyncEcommerce($storeId,$entityId,$type);
        $chimpSyncEcommerce->setMailchimpStoreId($storeId);
        $chimpSyncEcommerce->setType($type);
        $chimpSyncEcommerce->setRelatedId($entityId);
        $chimpSyncEcommerce->setMailchimpSyncModified($modified);
        $chimpSyncEcommerce->setMailchimpSyncDelta($date);
        $chimpSyncEcommerce->setMailchimpSyncError($error);
        $chimpSyncEcommerce->setMailchimpSyncDeleted($deleted);
        $chimpSyncEcommerce->setMailchimpToken($token);
        $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);
    }
    public function getChimpSyncEcommerce($storeId,$id,$type)
    {
        $chimp = $this->_mailChimpSyncEcommerce->create();
        return $chimp->getByStoreIdType($storeId,$id,$type);
    }
    public function getScope()
    {
    }
    public function loadStores()
    {
        $connection = $this->_mailChimpStores->getResource()->getConnection();
        $tableName = $this->_mailChimpStores->getResource()->getMainTable();
        $connection->truncateTable($tableName);
        $apiKeys = $this->getConfigValue(self::XML_PATH_APIKEY_LIST);
        $keys = explode("\n", $apiKeys);
        foreach($keys as $apiKey) {
            $this->_api->setApiKey(trim($apiKey));
            $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
            $apiStores = $this->_api->ecommerce->stores->get(null,null,null,self::MAXSTORES);
            foreach($apiStores['stores'] as $store) {
                if($store['platform']!=self::PLATFORM) {
                    continue;
                }
                $this->log($store);
                $mstore = $this->_mailChimpStoresFactory->create();
                $mstore->setApikey(trim($apiKey));
                $mstore->setStoreid($store['id']);
                $mstore->setListId($store['list_id']);
                $mstore->setName($store['name']);
                $mstore->setPlatform($store['platform']);
                $mstore->setIsSync($store['is_syncing']);
                $mstore->setEmailAddress($store['email_address']);
                $mstore->setCurrencyCode($store['currency_code']);
//                $mstore->setMoneyFormat($store['money_format']);
                $mstore->setPrimaryLocale($store['primary_locale']);
                $mstore->setTimezone($store['timezone']);
                $mstore->setPhone($store['phone']);
                $mstore->setAddressAddress1($store['address']['address1']);
                $mstore->setAddressAddress2($store['address']['address2']);
                $mstore->setAddressCity($store['address']['city']);
                $mstore->setAddressProvince($store['address']['province']);
                $mstore->setAddressProvinceCode($store['address']['province_code']);
                $mstore->setAddressPostalCode($store['address']['postal_code']);
                $mstore->setAddressCountry($store['address']['country']);
                $mstore->setAddressCountryCode($store['address']['country_code']);
                $mstore->getResource()->save($mstore);
            }
        }
    }
}
