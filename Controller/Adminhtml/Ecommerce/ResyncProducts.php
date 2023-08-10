<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\ValidatorException;

class ResyncProducts extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var SyncHelper
     */
    private $syncHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        SyncHelper $syncHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManagerInterface;
        $this->syncHelper = $syncHelper;
    }

    public function execute()
    {
        $valid = 1;
        $message = '';
        $params = $this->getRequest()->getParams();
        $mailchimpStore = $params['mailchimpStoreId'];
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->syncHelper->resyncProducts($mailchimpStore);
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
