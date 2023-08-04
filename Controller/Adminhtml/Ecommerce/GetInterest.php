<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class GetInterest extends Action
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
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
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $rc = [];
        $error = 0;
        if (array_key_exists('apikey', $param) && array_key_exists('list', $param)) {
            $apiKey = $param['apikey'];
            $list = $param['list'];
            $encrypt = $param['encrypt'];
            try {
                if ($encrypt == 3) {
                    $api = $this->_helper->getApi($this->_storeManager->getStore()->getId());
                } else {
                    $api = $this->_helper->getApiByApiKey($apiKey, $encrypt);
                }

                $result = $api->lists->interestCategory->getAll($list, null, null, 200);
                if (is_array($result['categories']) && count($result['categories'])) {
                    $rc = $result['categories'];
                }
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
                $error = 1;
            }
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['error' => $error, 'data' => $rc]);

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
