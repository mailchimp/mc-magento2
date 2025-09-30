<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Backend\App\Action;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManager;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Ebizmarts\MailChimp\Helper\Data as Helper;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;

class Register extends Action
{
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var MailChimpHttp
     */
    protected $_http;
    /**
     * @var Config
     */
    protected $_config;
    /**
     * @var StoreManager
     */
    protected $_storeManager;
    protected $_directoryHelper;

    /**
     * @param Context $context
     * @param Helper $helper
     * @param MailChimpHttp $http
     * @param Config $config
     * @param StoreManager $storeManager
     * @param DirectoryHelper $directoryHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Context $context,
        Helper $helper,
        MailChimpHttp $http,
        Config $config,
        StoreManager $storeManager,
        DirectoryHelper $directoryHelper
    )
    {
        parent::__construct($context);
        $this->_resultFactory       = $context->getResultFactory();
        $this->_helper = $helper;
        $this->_http = $http;
        $this->_http->setUrl($helper->getConfigValue(Helper::XML_REGISTER_URL).'/register');
        $this->_config = $config;
        $this->_storeManager = $storeManager;
        $this->_directoryHelper = $directoryHelper;
    }
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $scope = $params['scope'];
        $scopeId = $params['scopeId'];
        $registerData = [];
        $error = true;
        $token = $this->_helper->getConfigValue(Helper::XML_STATISTICS_TOKEN,$scopeId, $scope);
        foreach ($params['data'] as $index => $value) {
            $registerData[$index] = $value['value'];
        }
        $registerData['store_url'] = stripslashes($this->_storeManager->getStore($scopeId)->getBaseUrl());
        $registerData['city'] = $this->_helper->getConfigValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_CITY,$scopeId, $scope);
        $registerData['country'] = $this->_directoryHelper->getDefaultCountry($scopeId);
        $registerDataJson = json_encode($registerData);
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        if ($token) {
            $ret = $this->_http->patch($token, $registerDataJson);
        } else {
            $ret = $this->_http->post($registerDataJson);
        }
        $ret = json_decode($ret,true);
        if ( !$ret['error']) {
            $error = false;
            $token = $ret['token'];
            $this->_helper->saveConfigValue(Helper::XML_STATISTICS_TOKEN, $token,  $scopeId, $scope);
        }
        $resultJson->setData(['error' => $error, 'token' => $token]);
        return $resultJson;

    }
    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }

}
