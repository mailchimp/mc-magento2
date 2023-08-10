<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\ValidatorException;

class DeleteStore extends \Magento\Backend\App\Action
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
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_config;

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
        $this->_config = $config;
    }

    public function execute()
    {
        $valid = 1;
        $message = '';
        $params = $this->getRequest()->getParams();
        if (isset($params['website'])) {
            $scope = 'website';
            $storeId = $params['website'];
        } elseif (isset($params['store'])) {
            $scope = 'store';
            $storeId = $params['store'];
        } else {
            //$storeId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
            $storeId = 0;
            $scope = 'default';
        }

        $mailchimpStore = $this->helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $storeId,
            $scope
        );
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->helper->deleteStore($mailchimpStore);
            $this->_config->deleteConfig(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $scope, $storeId);
        } catch (ValidatorException $e) {
            $valid = 0;
            $message = $e->getMessage();
        }

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
