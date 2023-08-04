<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\ValidatorException;

class ResetStore extends \Magento\Backend\App\Action
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
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Ebizmarts\MailChimp\Helper\Data $helper
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
        if (isset($params['website'])) {
            $mailchimpStore = $this->helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $params['website'],
                'website'
            );
        } elseif (isset($params['store'])) {
            $mailchimpStore = $this->helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $params['store'],
                'store'
            );
        } else {
            $mailchimpStore = $this->helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $this->storeManager->getStore()
            );
        }

        try {
            $resultJson = $this->resultJsonFactory->create();
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
