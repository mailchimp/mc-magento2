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
    const XML_PATH_LIST              = 'mailchimp/general/monkeylist';
    const XML_PATH_WEBHOOK_ACTIVE    = 'mailchimp/general/webhook_active';
    const XML_PATH_WEBHOOK_DELETE    = 'mailchimp/general/webhook_delete';
    const XML_PATH_LOG               = 'mailchimp/general/log';
    const XML_PATH_MAPPING           = 'mailchimp/general/mapping';
    const XML_MAILCHIMP_STORE        = 'mailchimp/general/monkeystore';
    const XML_MAILCHIMP_JS_URL       = 'mailchimp/general/mailchimpjsurl';
    const XML_PATH_CONFIRMATION_FLAG = 'newsletter/subscription/confirm';
    const XML_PATH_STORE             = 'mailchimp/ecommerce/store';
    const XML_PATH_ECOMMERCE_ACTIVE  = 'mailchimp/ecommerce/active';
    const XML_PATH_SYNC_DATE         = 'mailchimp/general/mcminsyncdateflag';
    const XML_ECOMMERCE_OPTIN        = 'mailchimp/ecommerce/customer_optin';
    const XML_ECOMMERCE_FIRSTDATE    = 'mailchimp/ecommerce/firstdate';
    const XML_ABANDONEDCART_ACTIVE   = 'mailchimp/abandonedcart/active';
    const XML_ABANDONEDCART_FIRSTDATE   = 'mailchimp/abandonedcart/firstdate';
    const XML_ABANDONEDCART_PAGE     = 'mailchimp/abandonedcart/page';
    const XML_PATH_IS_SYNC           = 'mailchimp/general/issync';
    const XML_MERGEVARS              = 'mailchimp/general/map_fields';
    const XML_INTEREST               = 'mailchimp/general/interest';
    const XML_INTEREST_IN_SUCCESS    = 'mailchimp/general/interest_in_success';
    const XML_INTEREST_SUCCESS_HTML_BEFORE  = 'mailchimp/general/interest_success_html_before';
    const XML_INTEREST_SUCCESS_HTML_AFTER   = 'mailchimp/general/interest_success_html_after';
    const XML_MAGENTO_MAIL           = 'mailchimp/general/magentoemail';


    const ORDER_STATE_OK             = 'complete';

    const GUEST_GROUP                = 'NOT LOGGED IN';
    const IS_CUSTOMER   = "CUS";
    const IS_PRODUCT    = "PRO";
    const IS_ORDER      = "ORD";
    const IS_QUOTE      = "QUO";
    const IS_SUBSCRIBER = "SUB";
    const IS_PROMO_RULE = "PRL";
    const IS_PROMO_CODE = "PCD";

    const PLATFORM      = 'Magento2';
    const MAXSTORES     = 200;

    const SUB_MOD       = "SubscriberModified";
    const SUB_NEW       = "SubscriberNew";
    const PRO_MOD       = "ProductModified";
    const PRO_NEW       = "ProductNew";
    const CUS_MOD       = "CustomerModified";
    const CUS_NEW       = "CustomerNew";
    const ORD_MOD       = "OrderModified";
    const ORD_NEW       = "OrderNew";
    const QUO_MOD       = "QuoteModified";
    const QUO_NEW       = "QuoteNew";

    protected $counters = [];
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
     * @var \Magento\Framework\Encryption\Encryptor
     */
    private $_encryptor;
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $_subscriberCollection;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $_customerCollection;
    private $_addressRepositoryInterface;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $_resource;
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $_cacheTypeList;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    private $_attCollection;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $_interestGroupFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;


    private $customerAtt    = null;
    private $_mapFields     = null;

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
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $mailChimpErrors
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $mailChimpSyncEcommerce
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $mailChimpSyncE
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
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
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customer,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $mailChimpErrors,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $mailChimpSyncEcommerce,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $mailChimpSyncE,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory,
        \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
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
        $this->_encryptor               = $encryptor;
        $this->_subscriberCollection    = $subscriberCollection;
        $this->_customerCollection      = $customerCollection;
        $this->_addressRepositoryInterface = $addressRepositoryInterface;
        $this->_resource                = $resource;
        $this->connection               = $resource->getConnection();
        $this->_cacheTypeList           = $cacheTypeList;
        $this->_attCollection           = $attCollection;
        $this->_customerFactory         = $customerFactory;
        $this->_countryInformation      = $countryInformation;
        $this->_interestGroupFactory    = $interestGroupFactory;
        $this->_date                    = $date;
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
    public function getApiKey($store = null, $scope = null)
    {
        return $this->getConfigValue(self::XML_PATH_APIKEY, $store, $scope);
    }

    /**
     * @param null $store
     * @return \Mailchimp
     */
    public function getApi($store = null, $scope = null)
    {
        $apiKey = $this->getApiKey($store, $scope);
        $this->_api->setApiKey($apiKey);
        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        return $this->_api;
    }
    private function getCustomerAtts()
    {
        $ret = [];
        if(!$this->customerAtt) {
            $collection = $this->_attCollection->create();
            /**
             * @var $item \Magento\Customer\Model\Attribute
             */
            foreach ($collection as $item) {
                try {
                    if($item->usesSource()) {
                        $options = $item->getSource()->getAllOptions();
                    } else {
                        $options = [];
                    }

                } catch(\Exception $e) {
                    $options = [];
                }
                $isDate = ($item->getBackendModel()=='Magento\Eav\Model\Entity\Attribute\Backend\Datetime') ? 1:0;
                $isAddress = ($item->getBackendModel()=='Magento\Customer\Model\Customer\Attribute\Backend\Billing'||
                    $item->getBackendModel()=='Magento\Customer\Model\Customer\Attribute\Backend\Shipping') ? 1:0;
                $ret[$item->getId()] = ['attCode' => $item->getAttributeCode(), 'isDate' =>$isDate, 'isAddress' => $isAddress, 'options'=>$options] ;
            }

            $this->customerAtt = $ret;
        }
        return $this->customerAtt;

    }
    public function getMapFields($storeId = null)
    {
        if(!$this->_mapFields) {
            $customerAtt = $this->getCustomerAtts();
            $data = $this->getConfigValue(self::XML_MERGEVARS, $storeId);
            $data = unserialize($data);
            if(is_array($data)) {
                foreach ($data as $customerFieldId => $mailchimpName) {
                    $this->_mapFields[] = [
                        'mailchimp' => strtoupper($mailchimpName),
                        'customer_field' => $customerAtt[$customerFieldId]['attCode'],
                        'isDate' => $customerAtt[$customerFieldId]['isDate'],
                        'isAddress' => $customerAtt[$customerFieldId]['isAddress'],
                        'options' => $customerAtt[$customerFieldId]['options']
                    ];
                }
            }
        }
        return $this->_mapFields;
    }
    public function getDateFormat()
    {
        return 'm/d/Y';
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
        if($scope) {
            $value = $this->_scopeConfig->getValue($path, $scope, $storeId);
        }
        else {
            $value = $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
        }
        return $value;
    }
    public function deleteConfig($path, $storeId = null, $scope = null)
    {
        $this->_config->deleteConfig($path, $scope, $storeId);
    }

    public function saveConfigValue($path, $value, $storeId = null, $scope = null)
    {
        if($scope) {
            $this->_config->saveConfig($path, $value, $scope, $storeId);
        }
        else {
            $this->_config->saveConfig($path, $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
        }
        $this->_cacheTypeList->cleanType('config');
    }
    public function getMCMinSyncing($storeId)
    {
        $ret = $this->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC, $storeId);
        return !$ret;
    }
    public function getCartUrl($storeId,$cartId,$token)
    {
        $rc = $this->_storeManager->getStore($storeId)->getUrl(
            'mailchimp/cart/loadquote',
            [
                'id' => $cartId,
                'token' => $token,
                '_nosid' => true,
                '_secure' => true
            ]
        );
        return $rc;
    }
    public function getRedemptionUrl($storeId,$couponId,$token)
    {
        $rc = $this->_storeManager->getStore($storeId)->getUrl(
            'mailchimp/cart/loadcoupon',
            [
                'id' => $couponId,
                'token' => $token,
                '_nosid' => true,
                '_secure' => true
            ]
        );
        return $rc;
    }
    public function getSuccessInterestUrl($storeId)
    {
        $rc = $this->_storeManager->getStore($storeId)->getUrl(
            'mailchimp/checkout/success',
            [
                '_nosid' => true,
                '_secure' => true
            ]
        );
        return $rc;
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
        } catch(\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
    }
    public function markAllBatchesAs($mailchimpStore, $status)
    {
        $connection = $this->_syncBatches->getResource()->getConnection();
        $tableName = $this->_syncBatches->getResource()->getMainTable();
        $connection->update($tableName, ['status' => $status], "mailchimp_store_id = '".$mailchimpStore."'");
    }
    public function markRegisterAsModified($registerId, $type)
    {
        $connection = $this->_mailChimpSyncE->getResource()->getConnection();
        $tableName = $this->_mailChimpSyncE->getResource()->getMainTable();
        $connection->update($tableName,['mailchimp_sync_modified' => 1,'batch_id' => null],"type = '".$type."' and related_id = $registerId");
    }
    public function getMCStoreName($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getFrontendName();
    }
    public function getBaserUrl($storeId, $type)
    {
        return $this->_storeManager->getStore($storeId)->getBaseUrl($type);
    }
    public function createStore($listId = null, $storeId)
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
                $this->getApi()->ecommerce->stores->add($mailchimpStoreId, $listId, $name, $currencyCode, self::PLATFORM);
                return $mailchimpStoreId;
            } catch(\Mailchimp_Error $e) {
              $this->log($e->getFriendlyMessage());
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }
    public function getMCMinSyncDateFlag($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SYNC_DATE, $storeId);
    }
    public function getBaseDir()
    {
        return BP;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $storeId
     * @param null $email
     * @return array|null
     */
    public function getMergeVars(\Magento\Customer\Model\Customer $customer, $storeId)
    {
        $mergeVars = [];
        $mapFields = $this->getMapFields($storeId);
        if(is_array($mapFields)) {
            foreach ($mapFields as $map) {
                $value = $customer->getData($map['customer_field']);
                if ($value) {
                    if ($map['isDate']) {
                        $format = $this->getDateFormat();
                        if ($map['customer_field'] == 'dob') {
                            $format = substr($format, 0, 3);
                        }
                        $value = date($format, strtotime($value));
                    } elseif ($map['isAddress']) {
                        $customerAddress = $customer->getPrimaryAddress($map['customer_field']);
                        $value = [];
                        if ($customerAddress !== false) {
                            $value = $this->_getAddressValues($customerAddress);
                        }
                    } elseif (count($map['options'])) {
                        foreach ($map['options'] as $option) {
                            if ($option['value'] == $value) {
                                $value = $option['label'];
                                break;
                            }
                        }
                    }
                    $mergeVars[$map['mailchimp']] = $value;
                }
            }
        }
        return (!empty($mergeVars)) ? $mergeVars : null;
    }

    /**
     * @param \Magento\Customer\Model\Address\AbstractAddress $value
     * @return array
     */
    private function _getAddressValues(\Magento\Customer\Model\Address\AbstractAddress $address)
    {
        $addressData = array();
        if ($address) {
            $street = $address->getStreet();
            if (count($street) > 1) {
                $addressData["addr1"] = $street[0];
                $addressData["addr2"] = $street[1];
            } else {
                if (!empty($street[0])) {
                    $addressData["addr1"] = $street[0];
                }
            }
            if ($address->getCity()) {
                $addressData["city"] = $address->getCity();
            }
            if ($address->getRegion()) {
                $addressData["state"] = $address->getRegion();
            }
            if ($address->getPostcode()) {
                $addressData["zip"] = $address->getPostcode();
            }
            if ($address->getCountry()) {
                $country = $this->_countryInformation->getCountryInfo($address->getCountryId());
                $addressData["country"] = $country->getFullNameLocale();
            }
        }
        return $addressData;
    }

    public function getMergeVarsBySubscriber(\Magento\Newsletter\Model\Subscriber $subscriber, $email=null)
    {
        $mergeVars = [];
        $storeId = $subscriber->getStoreId();
        $webSiteId = $this->getWebsiteId($subscriber->getStoreId());
        if(!$email) {
            $email = $subscriber->getEmail();
        }
        try {
            /**
             * @var $customer \Magento\Customer\Model\Customer
             */
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($webSiteId);
            $customer->loadByEmail($email);
            if ($customer->getData('email') == $email) {
                $mergeVars = $this->getMergeVars($customer,$storeId);
            }
        }catch(\Exception $e) {
            $this->log($e->getMessage());
        }
        return $mergeVars;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $email
     * @return array|null
     */
    public function getMergeVarsByCustomer(\Magento\Customer\Model\Customer $customer, $email)
    {
        return $this->getMergeVars($customer,$customer->getStoreId());
    }


    public function getGeneralList($storeId)
    {
        return $this->getConfigValue(self::XML_PATH_LIST, $storeId);
    }

    public function getListForMailChimpStore($mailchimpStoreId, $apiKey)
    {
        try {
            $api = $this->getApiByApiKey($apiKey);
            $store = $api->ecommerce->stores->get($mailchimpStoreId);
            if (isset($store['list_id'])) {
                return $store['list_id'];
            }
        } catch (\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        }
        return null;
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
            // clean the syncecommerce table with errors
            $connection = $this->_mailChimpSyncE->getResource()->getConnection();
            $tableName = $this->_mailChimpSyncE->getResource()->getMainTable();
            $connection->delete($tableName, "mailchimp_store_id = '".$mailchimpStore."' and mailchimp_sync_error is not null");
//            $connection->commit();
//            $connection->truncateTable($tableName);
        } catch (\Zend_Db_Exception $e) {
            throw new ValidatorException(__($e->getMessage()));
        }
    }
    public function resetEcommerce()
    {
        $this->resetErrors();
    }
    public function saveEcommerceData($storeId, $entityId, $type, $date = null, $error = null , $modified = null, $deleted = null, $token = null)
    {

        $chimpSyncEcommerce = $this->getChimpSyncEcommerce($storeId, $entityId, $type);
        if($chimpSyncEcommerce->getRelatedId()==$entityId||!$chimpSyncEcommerce->getRelatedId()&&$modified!=1) {
            $chimpSyncEcommerce->setMailchimpStoreId($storeId);
            $chimpSyncEcommerce->setType($type);
            $chimpSyncEcommerce->setRelatedId($entityId);
            if ($modified) {
                $chimpSyncEcommerce->setMailchimpSyncModified($modified);
            }
            if ($date) {
                $chimpSyncEcommerce->setMailchimpSyncDelta($date);
            } elseif ($modified != 1) {
                $chimpSyncEcommerce->setBatchId(null);
            }
            if ($error) {
                $chimpSyncEcommerce->setMailchimpSyncError($error);
            }
            if ($deleted) {
                $chimpSyncEcommerce->setMailchimpSyncDeleted($deleted);
                $chimpSyncEcommerce->setMailchimpSyncModified(0);
            }
            if ($token) {
                $chimpSyncEcommerce->setMailchimpToken($token);
            }
            $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);
        }
    }

    public function markEcommerceAsModified($relatedId, $type)
    {
        $this->_mailChimpSyncE->markAllAsModified($relatedId,$type);
    }
    public function markEcommerceAsDeleted($relatedId, $type, $relatedDeletedId = null)
    {
        $this->_mailChimpSyncE->markAllAsDeleted($relatedId,$type, $relatedDeletedId);
    }
    public function ecommerceDeleteAllByIdType($id, $type, $mailchimpStoreId)
    {
        $this->_mailChimpSyncE->deleteAllByIdType($id, $type, $mailchimpStoreId);
    }

    public function getChimpSyncEcommerce($storeId, $id, $type)
    {
        $chimp = $this->_mailChimpSyncEcommerce->create();
        return $chimp->getByStoreIdType($storeId, $id, $type);
    }
    public function getScope()
    {
    }
    public function loadStores()
    {
        
        $mcUserName = [];
        $connection = $this->_mailChimpStores->getResource()->getConnection();
        $tableName = $this->_mailChimpStores->getResource()->getMainTable();
        $connection->truncateTable($tableName);
        $keys = $this->getAllApiKeys();
        foreach ($keys as $apiKey) {
            if (!$apiKey || $apiKey =='') {
                continue;
            }
            $this->_api->setApiKey(trim($apiKey));
            $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());

            try {
                $apiStores = $this->_api->ecommerce->stores->get(null, null, null, self::MAXSTORES);
            } catch(\Mailchimp_Error $mailchimpError) {
                $this->log($mailchimpError->getFriendlyMessage());
                continue;
            } catch(\Mailchimp_HttpError $mailchimpError) {
                $this->log($mailchimpError->getMessage());
                continue;
            }

            foreach ($apiStores['stores'] as $store) {
                if ($store['platform']!=self::PLATFORM) {
                    continue;
                }
                if(isset($store['connected_site'])) {
                    $name = $store['name'];
                } else {
                    $name = $store['name'].' (Warning: not connected)';
                }
                $mstore = $this->_mailChimpStoresFactory->create();
                $mstore->setApikey(trim($apiKey));
                $mstore->setStoreid($store['id']);
                $mstore->setListId($store['list_id']);
                $mstore->setName($name);
                $mstore->setPlatform($store['platform']);
                $mstore->setIsSync($store['is_syncing']);
                $mstore->setEmailAddress($store['email_address']);
                $mstore->setDomain($store['domain']);
                $mstore->setCurrencyCode($store['currency_code']);
//                $mstore->setMoneyFormat($store['money_format']);
                $mstore->setPrimaryLocale($store['primary_locale']);
                $mstore->setTimezone($store['timezone']);
                $mstore->setPhone($store['phone']);
                $mstore->setAddressAddressOne($store['address']['address1']);
                $mstore->setAddressAddressTwo($store['address']['address2']);
                $mstore->setAddressCity($store['address']['city']);
                $mstore->setAddressProvince($store['address']['province']);
                $mstore->setAddressProvinceCode($store['address']['province_code']);
                $mstore->setAddressPostalCode($store['address']['postal_code']);
                $mstore->setAddressCountry($store['address']['country']);
                $mstore->setAddressCountryCode($store['address']['country_code']);
                if (!isset($mcUserName[$apiKey])) {
                    $mcInfo = $this->_api->root->info();
                    $mcUserName[$apiKey] = $mcInfo['account_name'];
                }
                try {
                    $listInfo = $this->_api->lists->getLists($store['list_id']);
                    if (isset($listInfo['name'])) {
                        $mstore->setListName($listInfo['name']);
                        $mstore->setMcAccountName($mcUserName[$apiKey]);
                        $mstore->getResource()->save($mstore);
                    }
                } catch (\Mailchimp_Error $e) {
                    $this->log($e->getFriendlyMessage());
                }
            }
        }
    }
    public function getJsUrl($storeId)
    {
        $url = $this->getConfigValue(self::XML_MAILCHIMP_JS_URL, $storeId);
        if ($this->getConfigValue(self::XML_PATH_ACTIVE, $storeId) && !$url) {
            $mailChimpStoreId = $this->getConfigValue(self::XML_MAILCHIMP_STORE, $storeId);
            try {
                $api = $this->getApi($storeId);
                $storeData = $api->ecommerce->stores->get($mailChimpStoreId);
                if (isset($storeData['connected_site']['site_script']['url'])) {
                    $url = $storeData['connected_site']['site_script']['url'];
                    $this->_config->saveConfig(
                        self::XML_MAILCHIMP_JS_URL,
                        $url,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                        $storeId
                    );
                }
            } catch(\Mailchimp_Error $e) {
                $this->log($e->getFriendlyMessage());
            }
        }
        return $url;
    }

    public function getWebhooksKey()
    {
        $keys =explode("\n", $this->_encryptor->exportKeys());
        $crypt = md5((string)$keys[0]);
        $key = substr($crypt, 0, (strlen($crypt) / 2));

        return $key;
    }

    public function createWebHook($apikey, $listId)
    {
        $events = [
            'subscribe' => true,
            'unsubscribe' => true,
            'profile' => true,
            'cleaned' => true,
            'upemail' => true,
            'campaign' => false
        ];
        $sources = [
            'user' => true,
            'admin' => true,
            'api' => true
        ];
        try {
            $api = $this->getApiByApiKey($apikey);
            $hookUrl = $this->_getUrl(\Ebizmarts\MailChimp\Controller\WebHook\Index::WEBHOOK__PATH, [
            'wkey' => $this->getWebhooksKey(),
            '_nosid' => true,
            '_secure' => true]);
            // the urlencode of the hookUrl not work
            $ret = $api->lists->webhooks->add($listId, $hookUrl, $events, $sources);
        } catch (\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
            $ret ['message']= $e->getMessage();
        }
        return $ret;
    }
    public function deleteWebHook($apikey, $listId)
    {
        if (empty($listId)) {
            return;
        }
        try {
            $api = $this->getApiByApiKey($apikey);
            $webhooks = $api->lists->webhooks->getAll($listId);
            $hookUrl = $this->_getUrl(\Ebizmarts\MailChimp\Controller\WebHook\Index::WEBHOOK__PATH, [
                '_nosid' => true,
                '_secure' => true]);
            if (isset($webhooks['webhooks'])) {
                foreach ($webhooks['webhooks'] as $wh) {
                    if ($wh['url'] == $hookUrl) {
                        $api->lists->webhooks->delete($listId, $wh['id']);
                    }
                }
            }
        } catch(\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        }
    }

    /**
     * @param $listId
     * @param $mail
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function loadListSubscribers($listId, $mail)
    {
        $collection = null;
        $storeIds = $this->getMagentoStoreIdsByListId($listId);
        $storeIds[] = 0;
        if (count($storeIds) > 0) {
            $collection = $this->_subscriberCollection->create();
            $collection
                ->addFieldToFilter('store_id', ['in'=>$storeIds])
                ->addFieldToFilter('subscriber_email', ['eq'=>$mail]);
        }
        return $collection;
    }
    public function getMagentoStoreIdsByListId($listId)
    {
        $storeIds = [];
        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            if ($this->isMailChimpEnabled($storeId)) {
                $storeListId = $this->getConfigValue(self::XML_PATH_LIST, $storeId);
                if ($storeListId == $listId) {
                    $storeIds[] = $storeId;
                }
            }
        }
        return $storeIds;
    }

    /**
     * @param $listId
     * @param $email
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function loadListCustomers($listId, $email)
    {
        $customer = null;
        $storeIds = $this->getMagentoStoreIdsByListId($listId);
        if (count($storeIds) > 0) {
            $customer = $this->_customerCollection->create();
            $customer
                ->addFieldToSelect('entity_id')
                ->addFieldToFilter('store_id', ['in' => $storeIds])
                ->addFieldToFilter('email', ['eq' => $email]);
        }
        return $customer;
    }

    /**
     * @param $tableName
     * @return string
     */
    public function getTableName($tableName)
    {
        return $this->_resource->getTableName($tableName);
    }
    public function getWebsiteId($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getWebsiteId();
    }
    public function getInterest($storeId)
    {
        $rc = [];
        $interest = $this->getConfigValue(self::XML_INTEREST,$storeId);
        if($interest!='') {
            $interest = explode(",", $interest);
        } else {
            $interest = [];
        }
        try {
            $api = $this->getApi($storeId);
            $listId = $this->getConfigValue(self::XML_PATH_LIST, $storeId);
            $allInterest = $api->lists->interestCategory->getAll($listId);
            foreach ($allInterest['categories'] as $item) {
                if (in_array($item['id'], $interest)) {
                    $rc[$item['id']]['interest'] = ['id' => $item['id'], 'title' => $item['title'], 'type' => $item['type']];
                }
            }
            foreach ($interest as $interestId) {
                $mailchimpInterest = $api->lists->interestCategory->interests->getAll($listId, $interestId);
                foreach ($mailchimpInterest['interests'] as $mi) {
                    $rc[$mi['category_id']]['category'][$mi['display_order']] = ['id' => $mi['id'], 'name' => $mi['name'], 'checked' => false];
                }
            }
        } catch(\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        }
        return $rc;
    }
    public function getSubscriberInterest($subscriberId, $storeId, $interest = null)
    {
        if(!$interest) {
            $interest = $this->getInterest($storeId);
        }
        /**
         * @var $interestGroup \Ebizmarts\MailChimp\Model\MailChimpInterestGroup
         */

        $interestGroup = $this->_interestGroupFactory->create();
        $interestGroup->getBySubscriberIdStoreId($subscriberId,$storeId);
        $groups = unserialize($interestGroup->getGroupdata());
        if(isset($groups['group'])) {
            foreach ($groups['group'] as $key => $value) {
                if (isset($interest[$key])) {
                    if (is_array($value)) {
                        foreach ($value as $groupId) {
                            foreach ($interest[$key]['category'] as $gkey => $gvalue) {
                                if ($gvalue['id'] == $groupId) {
                                    $interest[$key]['category'][$gkey]['checked'] = true;
                                } elseif (!isset($interest[$key]['category'][$gkey]['checked'])) {
                                    $interest[$key]['category'][$gkey]['checked'] = false;
                                }
                            }
                        }
                    } else {
                        foreach ($interest[$key]['category'] as $gkey => $gvalue) {
                            if ($gvalue['id'] == $value) {
                                $interest[$key]['category'][$gkey]['checked'] = true;
                            } else {
                                $interest[$key]['category'][$gkey]['checked'] = false;
                            }
                        }

                    }
                }
            }
        }
        $this->log($interest);
        return $interest;
    }
    public function getGmtDate($format = null)
    {
        return $this->_date->gmtDate($format);
    }
    public function getGmtTimeStamp()
    {
        return $this->_date->gmtTimestamp();
    }
    public function getAllApiKeys() {
        $apiKeys = [];
        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            $apiKey = $this->getConfigValue(self::XML_PATH_APIKEY_LIST, $storeId);
            $tempApiKeys = explode("\n",$apiKey);
            foreach ($tempApiKeys as $tempAkiKey) {
                if(!in_array($tempAkiKey,$apiKeys)) {
                    $apiKeys[] = $tempAkiKey;
                }
            }
        }
        return $apiKeys;
    }
    public function modifyCounter($index, $increment=1)
    {
        if(array_key_exists($index,$this->counters)) {
            $this->counters[$index] = $this->counters[$index] + $increment;
        } else {
            $this->counters[$index] = 1;
        }
    }
    public function resetCounters()
    {
        $this->counters = [];
    }
    public function getCounters()
    {
        return $this->counters;
    }
}
