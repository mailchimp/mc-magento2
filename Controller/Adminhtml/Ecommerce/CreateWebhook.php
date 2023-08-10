<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Framework\Controller\Result\JsonFactory;

class CreateWebhook extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Config\Model\ResourceModel\Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Config\Model\ResourceModel\Config $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManagerInterface;
    }

    public function execute()
    {
        $valid = 1;
        $message = '';
        $params = $this->getRequest()->getParams();
        $apiKey = $params['apikey'];
        $listId = $params['listId'];
        $scope = $params['scope'];
        $scopeId = $params['scopeId'];

        if ($apiKey == '******') {
            $apiKey = $this->helper->getApiKey($scopeId, $scope);
        }

        $return = $this->helper->createWebHook($apiKey, $listId, $scope, $scopeId);
        if (isset($return['message'])) {
            $valid = 0;
            $message = $return['message'];
        }
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'valid' => (int)$valid,
            'message' => $message,
        ]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }
}
