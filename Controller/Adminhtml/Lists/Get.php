<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Lists;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Get extends Action
{
    const MAX_LISTS = 200;

    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $encryptor;

    /**
     * @param Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(
        Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\Encryption\Encryptor $encryptor
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->_helper = $helper;
        $this->encryptor = $encryptor;
    }

    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $this->encryptor->decrypt($param['apikey']);
        $result = [];
        try {
            $api = $this->_helper->getApiByApiKey($apiKey);
            $lists = $api->lists->getLists(null, null, null, self::MAX_LISTS);
            foreach ($lists['lists'] as $list) {
                $result['lists'][] = ['id' => $list['id'], 'name' => $list['name']];
            }
            $result['valid'] = 1;
        } catch (\Mailchimp_Error $e) {
            $result['valid'] = 0;
            $result['errormsg'] = $e->getTitle();
            $this->_helper->log($e->getFriendlyMessage());
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_edit');
    }
}
