<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/20/17 3:20 PM
 * @file: Get.php
 */
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
     * Get constructor.
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
        $this->_resultFactory       = $context->getResultFactory();
        $this->_helper                  = $helper;
        $this->encryptor                = $encryptor;
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
