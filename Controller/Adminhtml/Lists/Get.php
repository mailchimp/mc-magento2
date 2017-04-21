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
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;

    /**
     * Get constructor.
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper
    )
    {
        parent::__construct($context);
        $this->_resultFactory       = $resultFactory;
        $this->_helper                  = $helper;

    }
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $param['apikey'];
        $api = $this->_helper->getApiByApiKey($apiKey);
        $lists = $api->lists->getLists();
        $result = [];
        foreach($lists['lists'] as $list) {
            $result[] = ['id'=> $list['id'], 'name'=> $list['name']];
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);
        return $resultJson;
    }
}