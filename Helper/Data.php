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

use Magento\Customer\Api\Data\CustomerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Module\ModuleList\Loader;
use Magento\Framework\Encryption\Encryptor;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config;
use Ebizmarts\MailChimp\Model\Logger\Logger;
use Ebizmarts\MailChimp\Model\MailChimpSyncBatches;
use Ebizmarts\MailChimp\Model\MailChimpStoresFactory;
use Ebizmarts\MailChimp\Model\MailChimpStores;
use Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
    const XML_PATH_TIMEOUT           = 'mailchimp/general/timeout';
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
    const XML_ABANDONEDCART_EMAIL    = 'mailchimp/abandonedcart/save_email_in_quote';
    const XML_MERGEVARS              = 'mailchimp/general/map_fields';
    const XML_INTEREST               = 'mailchimp/general/interest';
    const XML_INTEREST_IN_SUCCESS    = 'mailchimp/general/interest_in_success';
    const XML_INTEREST_SUCCESS_HTML_BEFORE  = 'mailchimp/general/interest_success_html_before';
    const XML_INTEREST_SUCCESS_HTML_AFTER   = 'mailchimp/general/interest_success_html_after';
    const XML_MAGENTO_MAIL           = 'mailchimp/general/magentoemail';
    const XML_SEND_PROMO             = 'mailchimp/ecommerce/send_promo';
    const XML_INCLUDING_TAXES        = 'mailchimp/ecommerce/including_taxes';
    const XML_POPUP_FORM             = 'mailchimp/general/popup_form';
    const XML_POPUP_URL              = 'mailchimp/general/popup_url';
    const XML_CLEAN_ERROR_MONTHS     = 'mailchimp/ecommerce/clean_errors_months';
    const XML_FOOTER_PHONE           = 'mailchimp/general/footer_phone';
    const XML_FOOTER_MAP             = 'mailchimp/general/footer_phone_map';

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

    const SYNCED        = 1;
    const NEEDTORESYNC  = 2;
    const WAITINGSYNC   = 3;
    const SYNCERROR     = 4;
    const NOTSYNCED = 5;

    const NEVERSYNC     = 0;

    const BATCH_CANCELED = 'canceled';
    const BATCH_COMPLETED = 'completed';
    const BATCH_PENDING = 'pending';
    const BATCH_ERROR = 'error';

    const MAX_MERGEFIELDS = 100;

    protected $counters = [];
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var Logger
     */
    private $_mlogger;
    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var Loader
     */
    private $_loader;
    /**
     * @var Config
     */
    private $_config;
    /**
     * @var \Mailchimp
     */
    private $_api;
    /**
     * @var MailChimpSyncBatches
     */
    private $_syncBatches;
    /**
     * @var MailChimpStoresFactory
     */
    private $_mailChimpStoresFactory;
    /**
     * @var MailChimpStores
     */
    private $_mailChimpStores;
    /**
     * @var Encryptor
     */
    private $_encryptor;
    /**
     * @var SubscriberCollectionFactory
     */
    private $_subscriberCollection;
    /**
     * @var CustomerCollectionFactory
     */
    private $_customerCollection;
    /**
     * @var ResourceConnection
     */
    private $_resource;
    /**
     * @var TypeListInterface
     */
    private $_cacheTypeList;
    /**
     * @var AttributeCollectionFactory
     */
    private $_attCollection;
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var CountryInformationAcquirerInterface
     */
    protected $_countryInformation;
    /**
     * @var MailChimpInterestGroupFactory
     */
    protected $_interestGroupFactory;
    /**
     * @var DateTime
     */
    protected $_date;
    /**
     * @var CountryFactory
     */
    protected $countryFactory;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;


    private $customerAtt    = null;
    private $addressAtt     = null;
    private $_mapFields     = null;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Loader $loader
     * @param Config $config
     * @param \Mailchimp $api
     * @param TypeListInterface $cacheTypeList
     * @param MailChimpSyncBatches $syncBatches
     * @param MailChimpStoresFactory $mailChimpStoresFactory
     * @param MailChimpStores $mailChimpStores
     * @param AttributeCollectionFactory $attCollection
     * @param Encryptor $encryptor
     * @param SubscriberCollectionFactory $subscriberCollection
     * @param CustomerCollectionFactory $customerCollection
     * @param CustomerRepositoryInterface $customerRepository
     * @param CountryInformationAcquirerInterface $countryInformation
     * @param ResourceConnection $resource
     * @param MailChimpInterestGroupFactory $interestGroupFactory
     * @param DeploymentConfig $deploymentConfig
     * @param DateTime $date
     * @param CountryFactory $countryFactory
     * @param Session $customerSession
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Loader $loader,
        Config $config,
        \Mailchimp $api,
        TypeListInterface $cacheTypeList,
        MailChimpSyncBatches $syncBatches,
        MailChimpStoresFactory $mailChimpStoresFactory,
        MailChimpStores $mailChimpStores,
        AttributeCollectionFactory $attCollection,
        Encryptor $encryptor,
        SubscriberCollectionFactory $subscriberCollection,
        CustomerCollectionFactory $customerCollection,
        CustomerRepositoryInterface $customerRepository,
        CountryInformationAcquirerInterface $countryInformation,
        ResourceConnection $resource,
        MailChimpInterestGroupFactory $interestGroupFactory,
        DeploymentConfig $deploymentConfig,
        DateTime $date,
        CountryFactory $countryFactory,
        Session $customerSession,
        CustomerFactory $customerFactory
    ) {

        $this->_storeManager  = $storeManager;
        $this->_mlogger       = $logger;
        $this->_scopeConfig   = $context->getScopeConfig();
        $this->_request       = $context->getRequest();
        $this->_loader        = $loader;
        $this->_config        = $config;
        $this->_api           = $api;
        $this->_syncBatches             = $syncBatches;
        $this->_mailChimpStores         = $mailChimpStores;
        $this->_mailChimpStoresFactory  = $mailChimpStoresFactory;
        $this->_encryptor               = $encryptor;
        $this->_subscriberCollection    = $subscriberCollection;
        $this->_customerCollection      = $customerCollection;
        $this->_resource                = $resource;
        $this->_cacheTypeList           = $cacheTypeList;
        $this->_attCollection           = $attCollection;
        $this->_customerFactory         = $customerFactory;
        $this->_countryInformation      = $countryInformation;
        $this->_interestGroupFactory    = $interestGroupFactory;
        $this->_date                    = $date;
        $this->_deploymentConfig        = $deploymentConfig;
        $this->countryFactory           = $countryFactory;
        $this->customerSession          = $customerSession;
        $this->customerFactory          = $customerFactory;

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
        $apiKey =$this->getConfigValue(self::XML_PATH_APIKEY, $store, $scope);
        return $this->_encryptor->decrypt($apiKey);
    }
    public function getTimeOut($store=null, $scope=null)
    {
        return $this->getConfigValue(self::XML_PATH_TIMEOUT, $store, $scope);
    }
    /**
     * @param null $store
     * @return \Mailchimp
     */
    public function getApi($store = null, $scope = null)
    {
        $apiKey = $this->getApiKey($store, $scope);
        $timeOut = $this->getTimeOut($store,$scope);
        $this->_api->setApiKey($apiKey);
        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        if ($timeOut) {
            $this->_api->setTimeOut($timeOut);
        }
        return $this->_api;    }
    private function getBindableAttributes()
    {
        $systemAtt = $this->getCustomerAtts();
        $extraAtt = $this->getAddressAtt();

        // Note: We cannot use array_merge here because we need to hold
        // numeric indexes as they are
        $ret = $systemAtt + $extraAtt;

        return $ret;
    }
    private function getCustomerAtts()
    {
        $ret = [];
        if (!$this->customerAtt) {
            $collection = $this->_attCollection->create();
            /**
             * @var $item \Magento\Customer\Model\Attribute
             */
            foreach ($collection as $item) {
                try {
                    if ($item->usesSource()) {
                        $options = $item->getSource()->getAllOptions();
                    } else {
                        $options = [];
                    }
                } catch (\Exception $e) {
                    $options = [];
                }
                $isDate = ($item->getBackendModel()=='Magento\Eav\Model\Entity\Attribute\Backend\Datetime') ? 1:0;
                $isAddress = ($item->getBackendModel()=='Magento\Customer\Model\Customer\Attribute\Backend\Billing' ||
                    $item->getBackendModel()=='Magento\Customer\Model\Customer\Attribute\Backend\Shipping') ? 1:0;
                $ret[$item->getId()] = ['attCode' => $item->getAttributeCode(), 'isDate' =>$isDate, 'isAddress' => $isAddress, 'options'=>$options] ;
            }

            $this->customerAtt = $ret;
        }
        return $this->customerAtt;
    }
    private function getAddressAtt()
    {
        $ret = [];
        if (!$this->addressAtt) {
            $elements = [
                'default_shipping##zip',
                'default_shipping##country',
                'default_shipping##city',
                'default_shipping##state',
                'default_shipping##telephone',
                'default_shipping##company',
                'default_billing##zip',
                'default_billing##country',
                'default_billing##city',
                'default_billing##state',
                'default_billing##telephone',
                'default_billing##company'
            ];

            foreach($elements as $item) {
                $ret[$item] = [
                    'attCode'   => $item,
                    'isDate'    => false,
                    'isAddress' => true,
                    'options'   => []
                ];
            }

            $this->addressAtt = $ret;
        }

        return $this->addressAtt;
    }
    public function resetMapFields()
    {
        $this->_mapFields = null;
    }
    public function getMapFields($storeId = null)
    {
        if (!$this->_mapFields) {
            $customerAtt = $this->getBindableAttributes();
            $data = $this->getConfigValue(self::XML_MERGEVARS, $storeId);
            try {
                $data = $this->unserialize($data);
                if (is_array($data)) {
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
            } catch (\Exception $e) {
                $this->log($e->getMessage());
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
     * @param bool $encrypted
     * @return \Mailchimp
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApiByApiKey($apiKey, $encrypted=false)
    {
        if ($encrypted) {
            $this->_api->setApiKey($this->_encryptor->decrypt($apiKey));
        } else {
            $this->_api->setApiKey($apiKey);
        }
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
        if ($scope) {
            $value = $this->_scopeConfig->getValue($path, $scope, $storeId);
        } else {
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
        if ($scope) {
            $this->_config->saveConfig($path, $value, $scope, $storeId);
        } else {
            $this->_config->saveConfig($path, $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
        }
        $this->_cacheTypeList->cleanType('config');
    }
    public function getMCMinSyncing($storeId)
    {
        $ret = $this->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_IS_SYNC, $storeId);
        return !$ret;
    }
    public function getCartUrl($storeId, $cartId, $token)
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
    public function getRedemptionUrl($storeId, $couponId, $token)
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
            $this->cancelAllPendingBatches($mailchimpStore);
        } catch (\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
    }
    public function markAllBatchesAs($mailchimpStore, $fromStatus, $toStatus)
    {
        $connection = $this->_syncBatches->getResource()->getConnection();
        $tableName = $this->_syncBatches->getResource()->getMainTable();
        $connection->update($tableName, ['status' => $toStatus], "mailchimp_store_id = '" . $mailchimpStore . "' and status = '" . $fromStatus . "'");
    }

    public function cancelAllPendingBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore,self::BATCH_PENDING, self::BATCH_CANCELED);
    }

    public function restoreAllCanceledBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore,self::BATCH_CANCELED, self::BATCH_PENDING);
    }

    public function getMCStoreName($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getFrontendName();
    }
    public function getBaserUrl($storeId, $type)
    {
        return $this->_storeManager->getStore($storeId)->getBaseUrl($type, true);
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
            } catch (\Mailchimp_Error $e) {
                $this->log($e->getFriendlyMessage());
            } catch (Exception $e) {
                return null;
            }
        }
        return null;
    }
    public function getMCMinSyncDateFlag($storeId = null)
    {
        $syncDate = $this->getConfigValue(self::XML_PATH_SYNC_DATE, $storeId);
        if ($syncDate=='') {
            $syncDate = '1900-01-01';
        }
        return $syncDate;
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
        if (is_array($mapFields)) {
            foreach ($mapFields as $map) {
                $value = null;
                if (strpos($map['customer_field'], '##') !== false) {
                    $parts = explode('##', $map['customer_field']);
                    $attributeCode = $parts[0];
                    $fieldName = $parts[1];
                    $customerAddress = $customer->getPrimaryAddress($attributeCode);
                    if ($customerAddress !== false) {
                        if ($fieldName!='company') {
                            $addressData = $this->_getAddressValues($customerAddress);
                            if (!empty($addressData[$fieldName])) {
                                $value = $addressData[$fieldName];
                            }
                        } else {
                            $value = $customerAddress->getCompany();
                        }
                    }
                } else {
                    $value = $customer->getData($map['customer_field']);
                    if (!is_null($value)) {
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
                    }
                }

                if (!empty($value)) {
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
        $addressData = [];
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
            else {
                $addressData["state"] = "";
            }

            if ($address->getPostcode()) {
                $addressData["zip"] = $address->getPostcode();
            }
            if ($address->getCountry()) {
                $country = $this->countryFactory->create()->loadByCode($address->getCountryId());
                $addressData["country"] = $country->getName('en_US');
            }
            if ($address->getTelephone()) {
                $addressData['telephone'] = $address->getTelephone();
            }
        }
        return $addressData;
    }

    public function getMergeVarsBySubscriber(\Magento\Newsletter\Model\Subscriber $subscriber, $email = null)
    {
        $mergeVars = [];
        $webSiteId = $this->getWebsiteId($subscriber->getStoreId());
        if ($this->getConfigValue(self::XML_FOOTER_PHONE, $webSiteId, "websites")) {
            $phone_field = $this->getConfigValue(self::XML_FOOTER_MAP , $webSiteId, "websites");
            $phone = $subscriber->getPhone();
            if ($phone_field && $phone) {
                $mergeVars[$phone_field] = $phone;
            }
        }
        if (!$email) {
            $email = $subscriber->getEmail();
        }
        if ($this->customerSession->getCustomerId()) {
            try {
                /**
                 * @var $customer CustomerInterface
                 */
                $customer = $this->customerFactory->create()->load($this->customerSession->getCustomerId());
                $this->log("Customer ".$customer->getId());
                if ($customer->getData('mobile_phone')) {
                    $this->log($customer->getData('mobile_phone'));
                } else {
                    $this->log('no mobile phone');
                }
                if ($customer->getData('email') == $email) {
                    $mergeVars = array_merge($mergeVars, $this->getMergeVars($customer, $customer->getStoreId()));
                }
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        } else {
            $this->log("Subscriber is not a customer");
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
        return $this->getMergeVars($customer, $customer->getData('store_id'));
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
    public function loadStores()
    {

        $mcUserName = [];
        $allStores = [];
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
            } catch (\Mailchimp_Error $mailchimpError) {
                $this->log($mailchimpError->getFriendlyMessage());
                continue;
            } catch (\Mailchimp_HttpError $mailchimpError) {
                $this->log($mailchimpError->getMessage());
                continue;
            }

            foreach ($apiStores['stores'] as $store) {
                if ($store['platform']!=self::PLATFORM||in_array($store['id'],$allStores)) {
                    continue;
                }
                if (isset($store['connected_site'])) {
                    $name = $store['name'];
                } else {
                    $name = $store['name'].' (Warning: not connected)';
                }
                $allStores[] = $store['id'];
                $mstore = $this->_mailChimpStoresFactory->create();
                $mstore->setApikey($this->_encryptor->encrypt(trim($apiKey)));
                $mstore->setStoreid($store['id']);
                $mstore->setListId($store['list_id']);
                $mstore->setName($name);
                $mstore->setPlatform($store['platform']);
                $mstore->setIsSync($store['is_syncing']);
                $mstore->setEmailAddress($store['email_address']);
                $mstore->setDomain($store['domain']);
                $mstore->setCurrencyCode($store['currency_code']);
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
    public function saveJsUrl($storeId, $scope = null, $mailChimpStoreId = null)
    {
        if (!$scope) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        }
        if ($this->getConfigValue(self::XML_PATH_ACTIVE, $storeId, $scope)) {
            try {
                $api = $this->getApi($storeId);
                $storeData = $api->ecommerce->stores->get($mailChimpStoreId);
                if (isset($storeData['connected_site']['site_script']['url'])) {
                    $url = $storeData['connected_site']['site_script']['url'];
                    $this->_config->saveConfig(
                        self::XML_MAILCHIMP_JS_URL,
                        $url,
                        $scope,
                        $storeId
                    );
                }
            } catch (\Mailchimp_Error $e) {
                $this->log($e->getFriendlyMessage());
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
            } catch (\Mailchimp_Error $e) {
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

    public function createWebHook($apikey, $listId, $scope=null, $scopeId=null)
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
                '_scope' => $scopeId,
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
        } catch (\Mailchimp_Error $e) {
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
        $websiteIds = [];
        foreach($storeIds as $storeId) {
            $websiteIds[] =$this->_storeManager->getStore($storeId)->getWebsiteId();
        }
        if (count($storeIds) > 0) {
            $collection = $this->_subscriberCollection->create();
            $collection
                ->addFieldToFilter('store_id', ['in'=>$websiteIds])
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
     * @param string $conn
     * @return string
     */
    public function getTableName($tableName, $conn = ResourceConnection::DEFAULT_CONNECTION)
    {
        $dbName = $this->_deploymentConfig->get("db/connection/$conn/dbname");
        return $dbName.'.'.$this->_resource->getTableName($tableName, $conn);
    }
    public function getWebsiteId($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getWebsiteId();
    }
    public function getInterest($storeId)
    {
        $rc = [];
        $interest = $this->getConfigValue(self::XML_INTEREST, $storeId);
        if ($interest!='') {
            $interest = explode(",", $interest);
        } else {
            $interest = [];
        }
        try {
            $api = $this->getApi($storeId);
            $listId = $this->getConfigValue(self::XML_PATH_LIST, $storeId);
            $allInterest = $api->lists->interestCategory->getAll($listId, null, null, 200);
            if (is_array($allInterest) &&
                array_key_exists('categories', $allInterest) &&
                is_array($allInterest['categories'])) {
                foreach ($allInterest['categories'] as $item) {
                    if (in_array($item['id'], $interest)) {
                        $rc[$item['id']]['interest'] =
                            ['id' => $item['id'], 'title' => $item['title'], 'type' => $item['type']];
                    }
                }
                foreach ($interest as $interestId) {
                    $mailchimpInterest = $api->lists->interestCategory->interests->getAll($listId, $interestId, null, null, 200);
                    foreach ($mailchimpInterest['interests'] as $mi) {
                        $rc[$mi['category_id']]['category'][$mi['display_order']] =
                            ['id' => $mi['id'], 'name' => $mi['name'], 'checked' => false];
                    }
                }
            } else {
                $this->log(__('Error retrieving interest groups for store ').$storeId);
                $rc = [];
            }
        } catch (\Mailchimp_Error $e) {
            $this->log($e->getFriendlyMessage());
        }
        return $rc;
    }
    public function getSubscriberInterest($subscriberId, $storeId, $interest = null)
    {
        if (!$interest) {
            $interest = $this->getInterest($storeId);
        }
        /**
         * @var $interestGroup \Ebizmarts\MailChimp\Model\MailChimpInterestGroup
         */

        $interestGroup = $this->_interestGroupFactory->create();
        $interestGroup->getBySubscriberIdStoreId($subscriberId, $storeId);
        $serialized = $interestGroup->getGroupdata();
        if ($serialized&&is_array($interest)&&count($interest)) {
            try {
                $groups = $this->unserialize($serialized);
                if (isset($groups['group'])) {
                    foreach ($groups['group'] as $key => $value) {
                        if (array_key_exists($key, $interest)) {
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
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        }
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
    public function getAllApiKeys()
    {
        $apiKeys = [];
        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            $apiKey = $this->getApiKey($storeId);
            if (!in_array($apiKey, $apiKeys)) {
                $apiKeys[] = $apiKey;
            }
        }
        return $apiKeys;
    }

    public function modifyCounter($index, $increment = 1)
    {
        if (array_key_exists($index, $this->counters)) {
            $this->counters[$index] = $this->counters[$index] + $increment;
        } else {
            $this->counters[$index] = 1;
        }
    }

    public function resetCounters()
    {
        $this->counters = [
            self::SUB_NEW => 0,
            self::SUB_MOD => 0,
            self::ORD_NEW => 0,
            self::ORD_MOD => 0,
            self::PRO_NEW => 0,
            self::PRO_MOD => 0,
            self::CUS_NEW => 0,
            self::CUS_MOD => 0,
            self::QUO_NEW => 0,
            self::QUO_MOD => 0
        ];
    }

    public function getCounters()
    {
        return $this->counters;
    }

    public function getTotalNewItemsSent()
    {
        $totalAmount = 0;
        $itemArray = [self::ORD_NEW, self::SUB_NEW, self::PRO_NEW, self::CUS_NEW, self::QUO_NEW];

        foreach ($itemArray as $item) {
            if (array_key_exists($item, $this->counters)) {
                $totalAmount += $this->counters[$item];
            }
        }

        return $totalAmount;
    }

    public function serialize($data)
    {
        $result = json_encode($data);
        if (false === $result) {
            throw new \InvalidArgumentException('Unable to serialize value.');
        }
        return $result;
    }
    public function unserialize($string)
    {
        $result = json_decode($string, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Unable to unserialize value.');
        }
        return $result;
    }
    public function isEmailSavingEnabled($storeId)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_ABANDONEDCART_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
    public function decrypt($value) {
        return $this->_encryptor->decrypt($value);
    }
    public function encrypt($value)
    {
        return $this->_encryptor->encrypt($value);
    }
}
