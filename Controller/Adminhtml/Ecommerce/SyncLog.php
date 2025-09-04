<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ebizmarts\MailChimp\Helper\Data as MailchimpHelper;
use Ebizmarts\MailChimp\Helper\Http as MailChimpHttp;


class SyncLog extends Action
{
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var MailChimpHttp
     */
    protected $_http;
    /**
     * @var MailchimpHelper
     */
    protected $_helper;
    public function __construct(
        Context $context,
        MailchimpHelper $mailchimpHelper,
        MailchimpHttp $mailchimpHttp
    )
    {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->_helper = $mailchimpHelper;
        $this->_http = $mailchimpHttp;
        $this->_url = $mailchimpHelper->getConfigValue(MailchimpHelper::XML_REGISTER_URL);
    }
    public function execute()
    {
        $error = 0;
        $message = '';
        $params = $this->getRequest()->getParams();
        $scope = $params['scope'];
        $scopeId = $params['scopeId'];
        $onoff = $params['onoff'];
        $token = $this->_helper->getConfigValue(MailchimpHelper::XML_STATISTICS_TOKEN, $scopeId, $scope);
        if ($token) {
            if ($onoff) {
                $this->_http->setUrl($this->_url . '/switchon');
            } else {
                $this->_http->setUrl($this->_url . '/switchoff');
            }
            $response = $this->_http->put($token, null);
            $res = json_decode($response, true);
            if (key_exists('error',$res)) {
                $error = $res['error'];
                $message = $res['message'];
            }
        } else {
            $error = 1;
            $message = 'First register your copy';
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['error' => $error, 'message' => $message]);
        return $resultJson;
    }
}
