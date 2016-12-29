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
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACTIVE            = 'mailchimp/general/active';
    const XML_PATH_APIKEY            = 'mailchimp/general/apikey';
    const XML_PATH_MAXLISTAMOUNT     = 'mailchimp/general/maxlistamount';
    const XML_PATH_LIST              = 'mailchimp/general/list';
    const XML_PATH_LOG               = 'mailchimp/general/log';
    const XML_PATH_MAPPING           = 'mailchimp/general/mapping';
    const XML_PATH_CONFIRMATION_FLAG = 'newsletter/subscription/confirm';
    const XML_PATH_STORE             = 'mailchimp/ecommerce/store';
    const XML_PATH_ECOMMERCE_ACTIVE  = 'mailchimp/ecommerce/active';
    const XML_PATH_SYNC_DATE         = 'mailchimp/general/mcminsyncdateflag';
    const XML_ECOMMERCE_OPTIN        = 'mailchimp/ecommerce/customer_optin';

    const ORDER_STATE_OK             = 'complete';

    const MERGE_VARS                 = array(0 => array('magento' => 'fname', 'mailchimp' => 'FNAME'), 1 => array('magento' => 'lname', 'mailchimp' => 'LNAME'), 2 => array('magento' => 'gender', 'mailchimp' => 'GENDER'), 3 => array('magento' => 'dob', 'mailchimp' => 'DOB'), 4 => array('magento' => 'billing_address', 'mailchimp' => 'BILLING'), 5 => array('magento' => 'shipping_address', 'mailchimp' => 'SHIPPING'), 6 => array('magento' => 'billing_telephone', 'mailchimp' => 'BTELEPHONE'), 7 => array('magento' => 'shipping_telephone', 'mailchimp' => 'STELEPHONE'), 8 => array('magento' => 'billing_company', 'mailchimp' => 'BCOMPANY'), 9 => array('magento' => 'shipping_company', 'mailchimp' => 'SCOMPANY'), 10 => array('magento' => 'group_id', 'mailchimp' => 'CGROUP'), 11 => array('magento' => 'store_id', 'mailchimp' => 'STOREID'));
    const GUEST_GROUP                = 'NOT LOGGED IN';
    const IS_CUSTOMER   = "CUS";
    const IS_PRODUCT    = "PRO";
    const IS_ORDER      = "ORD";
    const IS_QUOTE      = "QUO";
    const IS_SUBSCRIBER = "SUB";

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
     * @var \Mailchimp
     */
    private $_api;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CustomerRepository
     */
    private $_customer;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Model\Logger\Logger $logger
     * @param \Magento\Customer\Model\GroupRegistry $groupRegistry
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param \Mailchimp $api
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Model\Logger\Logger $logger,
        \Magento\Customer\Model\GroupRegistry $groupRegistry,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Mailchimp $api,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
    ) {
    
        $this->_storeManager  = $storeManager;
        $this->_mlogger       = $logger;
        $this->_groupRegistry = $groupRegistry;
        $this->_scopeConfig   = $context->getScopeConfig();
        $this->_request       = $context->getRequest();
        $this->_state         = $state;
        $this->_loader        = $loader;
        $this->_api           = $api;
        $this->_customer      = $customer;
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
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfigValue($path, $storeId = null)
    {
        $areaCode = $this->_state->getAreaCode();
        if ($storeId !== null) {
            $configValue = $this->scopeConfig->getValue(
                $path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } elseif ($areaCode == 'frontend') {
            $frontStoreId = $this->_storeManager->getStore()->getId();
            $configValue = $this->scopeConfig->getValue(
                $path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $frontStoreId
            );
        } else {
            $storeId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $websiteId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
            if (!empty($storeId)) {
                $configValue = $this->scopeConfig->getValue(
                    $path,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            } elseif (!empty($websiteId)) {
                $configValue = $this->scopeConfig->getValue(
                    $path,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteId
                );
            } else {
                $configValue = $this->scopeConfig->getValue(
                    $path,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    0
                );
            }
        }
        return $configValue;
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
    public function log($message, $store = null)
    {
        if ($this->getConfigValue(self::XML_PATH_LOG, $store)) {
            $this->_mlogger->mailchimpLog($message);
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
    public function deleteStore()
    {
        try {
            $storeId = $this->getConfigValue(self::XML_PATH_STORE);
            $this->getApi()->ecommerce->stores->delete($storeId);
        } catch (Exception $e)
        {

        }
    }
    public function createStore($listId=null)
    {
        if ($listId) {
            //generate store id
            $date = date('Y-m-d-His');
            $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
            $storeId = parse_url($baseUrl, PHP_URL_HOST) . '_' . $date;
            $currencyCode = $this->_storeManager->getStore()->getDefaultCurrencyCode();
            //create store in mailchimp
            try {
                $this->getApi()->ecommerce->stores->add($storeId,$listId,$storeId,$currencyCode,'Magento');
                return $storeId;

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

}
