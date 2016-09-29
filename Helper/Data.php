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

use Magento\Store\Model\Store;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ACTIVE            = 'mailchimp/general/active';
    const XML_PATH_APIKEY            = 'mailchimp/general/apikey';
    const XML_PATH_MAXLISTAMOUNT     = 'mailchimp/general/maxlistamount';
    const XML_PATH_LIST              = 'mailchimp/general/list';
    const XML_PATH_LOG               = 'mailchimp/general/log';
    const XML_PATH_MAPPING           = 'mailchimp/general/mapping';
    const XML_PATH_CONFIRMATION_FLAG = 'newsletter/subscription/confirm';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Model\Logger\Logger
     */
    protected $_mlogger;
    /**
     * @var \Magento\Customer\Model\GroupRegistry
     */
    protected $_groupRegistry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;
    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    protected $_loader;
    /**
     * @var \Mailchimp
     */
    protected $_api;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Model\Logger\Logger $logger
     * @param \Magento\Customer\Model\GroupRegistry $groupRegistry
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param \Mailchimp $api
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Model\Logger\Logger $logger,
        \Magento\Customer\Model\GroupRegistry $groupRegistry,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        \Mailchimp $api
    ) {
    
        $this->_storeManager    = $storeManager;
        $this->_mlogger         = $logger;
        $this->_groupRegistry   = $groupRegistry;
        $this->_scopeConfig     = $context->getScopeConfig();
        $this->_request         = $context->getRequest();
        $this->_state           = $state;
        $this->_loader          = $loader;
        $this->_api             = $api;
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
     * @param \Mailchimp $pi
     * @param null $store
     * @return \Mailchimp
     */
    public function getApi($store = null)
    {
        $this->_api->setApiKey($this->getApiKey($store));
        $this->_api->setUserAgent('Mailchimp4Magento2' . (string)$this->getModuleVersion());
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
            $configValue = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        } elseif ($areaCode == 'frontend') {
            $frontStoreId = $this->_storeManager->getStore()->getId();
            $configValue = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $frontStoreId);
        } else {
            $storeId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $websiteId = $this->_request->getParam(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
            if (!empty($storeId)) {
                $configValue = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            } elseif (!empty($websiteId)) {
                $configValue = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
            } else {
                $configValue = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, 0);
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
        return $this->_scopeConfig->getValue(self::XML_PATH_LIST, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
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
//    public function getMergeVars($customer, $store = null)
//    {
//        $merge_vars = [];
//        $mergeVars  = unserialize($this->_scopeConfig->getValue(self::XML_PATH_MAPPING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store));
//
//        if (!$mergeVars) {
//            return $merge_vars;
//        }
//
//        foreach ($mergeVars as $map) {
//            $merge_vars = array_merge($merge_vars, $this->_getMergeVarsValue($map, $customer));
//        }
//        return $merge_vars;
//    }
//
//    protected function _getMergeVarsValue($map, $customer)
//    {
//        $merge_vars = [];
//        $customAtt = $map['magento'];
//        $chimpTag  = $map['mailchimp'];
//        if ($chimpTag && $customAtt) {
//            $key = strtoupper($chimpTag);
//            switch ($customAtt) {
//                case 'fname':
//                    $val = $customer->getFirstname();
//                    $merge_vars[$key] = $val;
//                    break;
//                case 'lname':
//                    $val = $customer->getLastname();
//                    $merge_vars[$key] = $val;
//                    break;
//                case 'gender':
//                    $val = (int)$customer->getData(strtolower($customAtt));
//                    if ($val == 1) {
//                        $merge_vars[$key] = 'Male';
//                    } elseif ($val == 2) {
//                        $merge_vars[$key] = 'Female';
//                    }
//                    break;
//                case 'dob':
//                    $dob = $customer->getData(strtolower($customAtt));
//                    if ($dob) {
//                        $merge_vars[$key] = (substr($dob, 5, 2) . '/' . substr($dob, 8, 2));
//                    }
//                    break;
//                case 'billing_address':
//                case 'shipping_address':
//                    $addr = explode('_', $customAtt);
//                    $merge_vars = array_merge($merge_vars, $this->_updateMergeVars($key, ucfirst($addr[0]), $customer));
//                    break;
//                case 'telephone':
//                    if ($address = $customer->{'getDefaultBillingAddress'}()) {
//                        $telephone = $address->getTelephone();
//                        if ($telephone) {
//                            $merge_vars[$key] = $telephone;
//                        }
//                    }
//                    break;
//                case 'company':
//                    if ($address = $customer->{'getDefaultBillingAddress'}()) {
//                        $company = $address->getCompany();
//                        if ($company) {
//                            $merge_vars[$key] = $company;
//                        }
//                    }
//                    break;
//                case 'group_id':
//                    $merge_vars = array_merge($merge_vars, $this->_getCustomerGroup($customer, $key));
//                    break;
//                default:
//                    if (($value = (string)$customer->getData(strtolower($customAtt)))) {
//                        $merge_vars[$key] = (string)$customer->getData(strtolower($customAtt));
//                    }
//                    break;
//            }
//            return $merge_vars;
//        }
//    }
//    protected function _getCustomerGroup($customer, $key)
//    {
//        $merge_vars = [];
//        $group_id = (int) $customer->getGroupId();
//        if ($group_id == 0) {
//            $merge_vars[$key] = 'NOT LOGGED IN';
//        } else {
//            try {
//                $customerGroup = $this->_groupRegistry->retrieve($group_id);
//                $merge_vars[$key] = $customerGroup->getCode();
//            } catch (\Exception $e) {
//                throw new \Exception($e->getMessage());
//            }
//        }
//        return $merge_vars;
//    }
//    protected function _updateMergeVars($key, $type, $customer)
//    {
//        $merge_vars = [];
//        if ($address = $customer->{'getDefault' . $type . 'Address'}()) {
//            $merge_vars[$key] = [
//                'addr1' => $address->getStreetLine(1),
//                'addr2' => $address->getStreetLine(2),
//                'city' => $address->getCity(),
//                'state' => (!$address->getRegion() ? $address->getCity() : $address->getRegion()),
//                'zip' => $address->getPostcode(),
//                'country' => $address->getCountryId()
//            ];
//        }
//        return $merge_vars;
//    }
}
