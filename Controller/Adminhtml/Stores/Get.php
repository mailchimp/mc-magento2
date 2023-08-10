<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Get extends Action
{
    const MAX_STORES = 200;

    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_mhelper;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;

    /**
     * @param Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->_mhelper = $helper;
    }

    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $param['apikey'];
        $encrypt = $param['encrypt'];
        try {
            $api = $this->_mhelper->getApiByApiKey($apiKey, $encrypt);
            $stores = $api->ecommerce->stores->get(null, null, null, self::MAX_STORES);
            $result = [];
            $result['valid'] = 1;
            $result['stores'] = [];
            foreach ($stores['stores'] as $store) {
                if ($store['platform'] == \Ebizmarts\MailChimp\Helper\Data::PLATFORM) {
                    if ($store['list_id'] == '') {
                        continue;
                    }
                    $list = $api->lists->getLists($store['list_id']);
                    $result['stores'][] = [
                        'id' => $store['id'],
                        'name' => $store['name'],
                        'list_name' => $list['name'],
                        'list_id' => $store['list_id']
                    ];
                    $result['valid'] = 1;
                }
            }
        } catch (\Mailchimp_Error $e) {
            $this->_mhelper->log($e->getFriendlyMessage());
            $result['valid'] = 0;
            $result['errormsg'] = $e->getTitle();
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }
}
