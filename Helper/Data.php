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

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\Store;
use Ebizmarts\MailChimp\Model\MailchimpNotificationFactory as MailchimpNotificationFactory;

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
    const XML_PATH_ALL_CUSTOMERS     = 'mailchimp/ecommerce/all_customers';
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
    const XML_SYNC_SALABLE             = 'mailchimp/ecommerce/syncsalable';
    const XML_INCLUDING_TAXES        = 'mailchimp/ecommerce/including_taxes';
    const XML_POPUP_FORM             = 'mailchimp/general/popup_form';
    const XML_POPUP_URL              = 'mailchimp/general/popup_url';
    const XML_CLEAN_ERROR_MONTHS     = 'mailchimp/ecommerce/clean_errors_months';
    const XML_ENABLE_SUPPORT         = 'mailchimp/general/enable_support';
    const SYNC_TOKEN                 = 'mailchimp/statistics/token';
    const SYNC_NOTIFICATION_URL       = 'mailchimp/statistics/notification_url';
    const XML_CLEAN_SUPPORT_PERIOD    = 'mailchimp/general/clean_support_period';
    const XML_REGISTER_URL            = 'mailchimp/statistics/register_url';
    const XML_STATISTICS_TOKEN        = 'mailchimp/register/token';

    /**
     * Status reporting beacon. The token is per store view; the cron expression is
     * per install; the delivery uid is the last notification actually written
     * to the admin inbox. Deliberately NOT mailchimp/register/token, which
     * holds a token from the previous relay and is meaningless to this service.
     */
    const XML_EDGE_TOKEN              = 'mailchimp/register/edge_token';
    const XML_EDGE_BEACON_CRON        = 'mailchimp/register/beacon_cron';
    const XML_EDGE_DELIVERY_UID       = 'mailchimp/register/last_delivery_uid';

    /**
     * Whether the merchant lets the account owner's name and address travel
     * with diagnostics.
     *
     * The path is shared with the API library on purpose: one switch in the
     * admin has to govern every lane that reports, or it describes only part
     * of what leaves the server.
     *
     * The library reads it from the release that first gives it anything to
     * report — the constant and the reporting arrived in the same commit
     * there, so no version of it can send contact details while ignoring this.
     * Until that release is the one installed, the switch governs this
     * extension's reports and there is nothing else for it to govern.
     */
    const XML_TELEMETRY_SHARE_CONTACT = 'mailchimp/telemetry/share_contact';

    const XML_PIXEL_ENABLED_FOR_STORE = 'mailchimp/pixel/enabled_for_store';
    const XML_PIXEL_SCRIPT_URL        = 'mailchimp/pixel/script_url';
    const XML_PIXEL_SCRIPT_FRAGMENT   = 'mailchimp/pixel/script_fragment';

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
    const PRO_DELETED   = "ProductDeleted";
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

    const MIN_LIB_VERSION = '3.0.45';

    protected $counters = [];

    /**
     * API keys that already failed in this process, by fingerprint.
     *
     * Process-scoped on purpose. A rejected credential is an answer, not a
     * blip: it will be just as rejected for the next store view a millisecond
     * later. Nothing is written anywhere, so the next cron run starts clean and
     * a key fixed between runs is picked up immediately.
     *
     * @var array
     */
    private $failedApiKeys = [];
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Model\Logger\Logger
     */
    private $_mlogger;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
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
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $_deploymentConfig;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_serializer;
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;
    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    /**
     * @var MailchimpNotificationFactory
     */
    protected $mailchimpNotificationFactory;

    protected $resolver;
    private $customerAtt    = null;
    private $addressAtt     = null;
    private $_mapFields     = null;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Model\Logger\Logger $logger
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Mailchimp $api
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches
     * @param \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param ResourceConnection $resource
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param MailchimpNotificationFactory $mailchimpNotificationFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Model\Logger\Logger $logger,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Mailchimp $api,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $syncBatches,
        \Ebizmarts\MailChimp\Model\MailChimpStoresFactory $mailChimpStoresFactory,
        \Ebizmarts\MailChimp\Model\MailChimpStores $mailChimpStores,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attCollection,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Locale\Resolver $resolver,
        MailchimpNotificationFactory $mailchimpNotificationFactory
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
        $this->_serializer              = $serializer;
        $this->_deploymentConfig        = $deploymentConfig;
        $this->_date                    = $date;
        $this->countryFactory           = $countryFactory;
        $this->resolver                 = $resolver;
        $this->mailchimpNotificationFactory = $mailchimpNotificationFactory;
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
    public function isSupportEnabled()
    {
        return $this->getConfigValue(self::XML_ENABLE_SUPPORT);
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
        $this->_api->setHelper($this);
        $this->_api->setStoreURL($this->_storeManager->getStore($store)->getBaseUrl());
        // The library cannot observe this one. It is visible in a path only
        // when a caller addresses a store directly, and the ecommerce sync
        // posts all of its per-store work to `batches` with the store id in
        // the body — which the library deliberately never reads.
        //
        // Guarded because composer.json is not always what decides which
        // library is present. An app/code install puts this extension on disk
        // by clone and the library comes from a separate composer require, so
        // the `>=3.0.47` constraint below is inert there and the two can be
        // updated independently. Without this check that pairing is a fatal on
        // every call into the API, which is most of the extension.
        if (method_exists($this->_api, 'setMailchimpStoreId')) {
            $this->_api->setMailchimpStoreId($this->getConfigValue(self::XML_MAILCHIMP_STORE, $store, $scope));
        }
        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        if ($timeOut) {
            $this->_api->setTimeOut($timeOut);
        }
        return $this->_api;
    }
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
                $isDate = ($item->getBackendModel()==\Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class) ? 1:0;
                $isAddress = (
                    $item->getBackendModel()==\Magento\Customer\Model\Customer\Attribute\Backend\Billing::class ||
                    $item->getBackendModel()==\Magento\Customer\Model\Customer\Attribute\Backend\Shipping::class) ? 1:0;
                $ret[$item->getId()] = [
                    'attCode' => $item->getAttributeCode(),
                    'isDate' =>$isDate,
                    'isAddress' => $isAddress,
                    'options'=>$options
                ] ;
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
                'default_shipping##street',
                'default_billing##zip',
                'default_billing##country',
                'default_billing##city',
                'default_billing##state',
                'default_billing##telephone',
                'default_billing##company',
                'default_billing##street'
            ];

            foreach($elements as $item) {
                $ret[$item] = [
                    'attCode'   => $item,
                    'isDate'    => false,
                    'isAddress' => false,
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
    public function getMapFields($storeId = null, $options=true)
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
                            'options' => $options ? $customerAtt[$customerFieldId]['options'] : false
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
    public function getApiByApiKey($apiKey, $encrypted = false)
    {
        if ($encrypted) {
            $this->_api->setApiKey($this->_encryptor->decrypt($apiKey));
        } else {
            $this->_api->setApiKey($apiKey);
        }

        $this->_api->setUserAgent('Mailchimp4Magento' . (string)$this->getModuleVersion());
        $this->_api->setHelper($this);
        $this->_api->setStoreURL($this->_storeManager->getStore()->getBaseUrl());
        if (method_exists($this->_api, 'setMailchimpStoreId')) {
            $this->_api->setMailchimpStoreId($this->getConfigValue(self::XML_MAILCHIMP_STORE));
        }

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

    /**
     * Write a config value now and leave the cache flush to the caller.
     *
     * saveConfigValue() flushes on every call, and a flush is not cheap: the
     * config module intercepts it to re-read the whole system config and then
     * serialise and encrypt every scope in turn, under a lock. That cost grows
     * with the number of store views, so a job that writes once per view pays
     * it once per view — quadratic work for what should be one refresh.
     *
     * Callers that write several values in one pass should use this and then
     * call flushConfigCache() once. Nothing they write is visible to a read
     * until they do, so this is only safe where the caller does not read back
     * what it has just written.
     *
     * @param  string      $path
     * @param  string      $value
     * @param  int|null    $storeId
     * @param  string|null $scope
     * @return void
     */
    public function saveConfigValueWithoutCacheFlush($path, $value, $storeId = null, $scope = null)
    {
        $this->saveConfigValueAtomic(
            (string)$path,
            (string)$value,
            (string)($scope ?: \Magento\Store\Model\ScopeInterface::SCOPE_STORES),
            (int)$storeId
        );
    }

    /**
     * Refresh the config cache once, after a run of deferred writes.
     *
     * @return void
     */
    public function flushConfigCache()
    {
        $this->_cacheTypeList->cleanType('config');
    }

    /**
     * Atomically upsert a single config row using INSERT … ON DUPLICATE KEY UPDATE.
     *
     * core_config_data has a UNIQUE KEY on (scope, scope_id, path), so a plain
     * INSERT … ON DUPLICATE KEY UPDATE is one round-trip and race-free — unlike
     * Magento's ResourceModel\Config::saveConfig() which does a SELECT then
     * INSERT/UPDATE (TOCTOU window under concurrent cron workers).
     *
     * Does NOT flush the config cache — callers that need an immediate cache
     * refresh should call $this->_cacheTypeList->cleanType('config') themselves.
     */
    private function saveConfigValueAtomic(string $path, string $value, string $scope, int $scopeId): void
    {
        $table = $this->_resource->getTableName('core_config_data');
        $this->_resource->getConnection()->insertOnDuplicate(
            $table,
            ['scope' => $scope, 'scope_id' => $scopeId, 'path' => $path, 'value' => $value],
            ['value']
        );
    }

    /**
     * Write the issync flag for a Mailchimp store.
     *
     * Only the per-mailchimpStore path (issync/<mcStoreId>) is ever written.
     * Writing the bare scalar issync alongside issync/<id> children at the
     * same scope causes Magento\Framework\App\Config\Scope\Converter to throw
     * "Cannot access offset of type string on string" — a site-wide fatal.
     *
     * The write is atomic (INSERT … ON DUPLICATE KEY UPDATE) to prevent the
     * check-then-write race that produces duplicate core_config_data rows under
     * concurrent cron workers.
     *
     * @param string $mailchimpStoreId
     * @param string $value
     * @param int    $scopeId
     * @param string $scope
     */
    public function saveMCMinSyncing($mailchimpStoreId, $value, $scopeId = 0, $scope = 'default')
    {
        if (!$mailchimpStoreId) {
            // Bare-scalar write intentionally removed — see docblock above.
            return;
        }
        $this->saveConfigValueAtomic(
            self::XML_PATH_IS_SYNC . '/' . $mailchimpStoreId,
            (string) $value,
            (string) $scope,
            (int) $scopeId
        );
        // saveConfigValueAtomic writes straight to the table; flush the config
        // cache so the freshly written flag is visible to the next read (the cart
        // sync gate depends on this).
        $this->_cacheTypeList->cleanType('config');
    }

    /**
     * Clear the "initial sync complete" flag for a Mailchimp store. After a store
     * is deleted/reset the cart sync must stay blocked until the store has fully
     * re-synced, so the per-store issync/<id> flag is removed here.
     *
     * @param string $mailchimpStoreId
     * @param int $scopeId
     * @param string $scope
     */
    public function deleteMCMinSyncing($mailchimpStoreId, $scopeId = 0, $scope = 'default')
    {
        if (!$mailchimpStoreId) {
            return;
        }
        $this->deleteConfig(
            self::XML_PATH_IS_SYNC . '/' . $mailchimpStoreId,
            (int) $scopeId,
            (string) $scope
        );
        $this->_cacheTypeList->cleanType('config');
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
    public function saveNotification($data)
    {
        $mailchimpNotification = $this->mailchimpNotificationFactory->create();
        $mailchimpNotification->setNotificationData(json_encode($data));
        $mailchimpNotification->setProcessed(false);
        $mailchimpNotification->getResource()->save($mailchimpNotification);

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
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->log($e->getFriendlyMessage());
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }
    }
    public function markAllBatchesAs($mailchimpStore, $fromStatus, $toStatus)
    {
        $connection = $this->_syncBatches->getResource()->getConnection();
        $tableName = $this->_syncBatches->getResource()->getMainTable();
        $connection->update(
            $tableName,
            ['status' => $toStatus],
            "mailchimp_store_id = '" . $mailchimpStore . "' and status = '" . $fromStatus . "'"
        );
    }

    public function cancelAllPendingBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore, self::BATCH_PENDING, self::BATCH_CANCELED);
    }

    public function restoreAllCanceledBatches($mailchimpStore)
    {
        $this->markAllBatchesAs($mailchimpStore, self::BATCH_CANCELED, self::BATCH_PENDING);
    }

    public function getMCStoreName($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getFrontendName();
    }
    public function getBaserUrl($storeId, $type)
    {
        return $this->_storeManager->getStore($storeId)->getBaseUrl($type, true);
    }
    public function getMCMinSyncDateFlag($storeId = null)
    {
        $syncDate = $this->getConfigValue(self::XML_PATH_IS_SYNC, $storeId);
        // When a per-Mailchimp-store flag has been written under issync/<mailchimpStoreId>
        // at default scope, this path resolves to an array for any store with no scalar
        // override of its own. Such a store has not recorded a completion date, so treat
        // it as "not yet synced" instead of returning the array to the callers.
        if (is_array($syncDate) || $syncDate === null || $syncDate == '') {
            $syncDate = '1900-01-01';
        }
        return $syncDate;
    }
    public function getMCMinSyncDateFlagByMailchimpStore($mailchimpStoreId = null, $storeId = null, $scope = null)
    {
        $syncDate = $this->getConfigValue(self::XML_PATH_IS_SYNC. "/$mailchimpStoreId", $storeId, $scope);
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
                }
                else {
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
                                    $value = __($option['label']);
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!empty($value)) {
                    $mergeVars[$map['mailchimp']] = $value;
                } else {
                    $mergeVars[$map['mailchimp']] = '';
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
                $addressData["street"] = $street[0].' '.$street[1];
            } else {
                if (!empty($street[0])) {
                    $addressData["street"] = $street[0];
                }
            }
            if ($address->getCity()) {
                $addressData["city"] = $address->getCity();
            }
            if ($address->getRegion()) {
                $addressData["state"] = $address->getRegion();
            } else {
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
        $storeId = $subscriber->getStoreId();
        $webSiteId = $this->getWebsiteId($subscriber->getStoreId());
        if (!$email) {
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
                $mergeVars = $this->getMergeVars($customer, $storeId);
            }
        } catch (\Exception $e) {
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
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
            $this->_api->setHelper($this);


            try {
                $apiStores = $this->_api->ecommerce->stores->get(null, null, null, self::MAXSTORES);
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                $this->log($e->getFriendlyMessage());
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
                } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                $this->log($e->getFriendlyMessage());
            }
        }
        return $url;
    }

    public function getWebhooksKey()
    {
        $keys =explode("\n", $this->_encryptor->exportKeys());
        $crypt = hash('md5', (string)$keys[0]);
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
            // Do not subscribe to API-originated events: the extension writes members
            // through the API (customer/subscriber sync), and echoing those changes back
            // as webhooks creates a feedback loop that recreates newsletter subscribers.
            'api' => false
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
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
     * @param string $conn
     * @return string
     */
    public function getTableName($tableName, $conn = ResourceConnection::DEFAULT_CONNECTION)
    {
        $connection = $this->_resource->getConnection($conn);
        $tablePrefix = $this->_resource->getTablePrefix();
        if ($tablePrefix && strpos($tableName, $tablePrefix) !== 0) {
            $tableName = $tablePrefix . $tableName;
        }
         return $connection->getTableName($tableName, $conn);
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
                    $mailchimpInterest = $api->lists->interestCategory->interests->getAll($listId, $interestId, null, null,200);
                    foreach ($mailchimpInterest['interests'] as $mi) {
                        $rc[$mi['category_id']]['category'][$mi['display_order']] =
                            ['id' => $mi['id'], 'name' => $mi['name'], 'checked' => false];
                    }
                }
            } else {
                $this->log(__('Error retrieving interest groups for store ').$storeId);
                $rc = [];
            }
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                $this->log($e->getFriendlyMessage());
            } catch (Exception $e) {
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
    public function resetCounters($storeId = null)
    {
        $this->counters = [];
        $this->counters = [
            self::SUB_NEW => 0,
            self::SUB_MOD => 0,
            self::ORD_NEW => 0,
            self::ORD_MOD => 0,
            self::PRO_NEW => 0,
            self::PRO_DELETED => 0,
            self::PRO_MOD => 0,
            self::QUO_NEW => 0,
            self::QUO_MOD => 0
        ];
        if ($this->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ALL_CUSTOMERS, $storeId)) {
            $this->counters [self::CUS_NEW] = 0;
            $this->counters [self::CUS_MOD] = 0;
        }

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
        return $this->_serializer->serialize($data);
    }
    public function unserialize($string)
    {
        return $this->_serializer->unserialize($string);
    }
    public function isEmailSavingEnabled($storeId)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_ABANDONEDCART_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
    public function decrypt($value)
    {
        return $this->_encryptor->decrypt($value);
    }
    public function encrypt($value)
    {
        return $this->_encryptor->encrypt($value);
    }
    public function buttonPressed($button, $result)
    {
        $data = [];
        $data['storeURL'] = $this->_storeManager->getStore()->getBaseUrl();
        $data['time'] = $this->getGmtDate();
        $data['button']['action'] = $button;
        $data['button']['result'] = $result;
        $this->saveNotification($data);
    }
    public function switchLog($on)
    {
        $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        $scope = 'default';
        $token = $this->getConfigValue(self::XML_STATISTICS_TOKEN, $storeId, $scope);

    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    public function sendCartEvent($quote)
    {
        $storeId = $quote->getStoreId();
        $api = $this->getApi($storeId);
        $list = $this->getDefaultList($storeId);
        $customerMailchimpId = hash('md5', strtolower($quote->getCustomerEmail()));
        $properties = [];
        $properties['quote_id'] = $quote->getId();
        $properties['store_id'] = $this->getConfigValue(self::XML_MAILCHIMP_STORE, $storeId);
        $properties['customer_email'] = $quote->getCustomerEmail();
        $properties['total'] = $quote->getGrandTotal();
        $properties['currency'] = $quote->getQuoteCurrencyCode();
        $properties['customer_id'] = $quote->getCustomerId();
        $api->lists->members->memberEvent->add($list, $customerMailchimpId, "abandoned_cart_visit",$properties,false,$this->getGmtDate());
    }

    /**
     * Returns true when the Mailchimp Pixel is enabled for the given store.
     *
     * @param int $storeId
     * @return bool
     */
    public function isPixelEnabled($storeId)
    {
        return (bool)$this->getConfigValue(self::XML_PIXEL_ENABLED_FOR_STORE, $storeId);
    }

    /**
     * Returns the Pixel SDK script URL saved for the given store.
     *
     * @param int $storeId
     * @return string
     */
    public function getPixelScriptUrl($storeId)
    {
        return (string)$this->getConfigValue(self::XML_PIXEL_SCRIPT_URL, $storeId);
    }

    /**
     * Remember that this store's API key failed, so callers can stop asking.
     *
     * Keyed on the credential rather than on the store, which is the whole
     * point: one key shared across many store views is exactly the shape that
     * turns a single rejection into one round trip per view.
     *
     * @param  int|null    $store
     * @param  string|null $scope
     * @return void
     */
    public function markApiKeyFailed($store = null, $scope = null)
    {
        $fingerprint = $this->apiKeyFingerprint($store, $scope);
        if ($fingerprint !== null) {
            $this->failedApiKeys[$fingerprint] = true;
        }
    }

    /**
     * Whether this store's API key has already failed in this process.
     *
     * @param  int|null    $store
     * @param  string|null $scope
     * @return bool
     */
    public function isApiKeyFailed($store = null, $scope = null)
    {
        $fingerprint = $this->apiKeyFingerprint($store, $scope);

        return $fingerprint !== null && isset($this->failedApiKeys[$fingerprint]);
    }

    /**
     * A stable handle for a key that never holds the key itself.
     *
     * Null for an empty key: a store with nothing configured has no credential
     * to blame, and lumping them all under one empty fingerprint would let an
     * unconfigured store silence a configured one.
     *
     * @param  int|null    $store
     * @param  string|null $scope
     * @return string|null
     */
    private function apiKeyFingerprint($store = null, $scope = null)
    {
        $apiKey = (string)$this->getApiKey($store, $scope);
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return null;
        }

        return hash('sha256', $apiKey);
    }
}
