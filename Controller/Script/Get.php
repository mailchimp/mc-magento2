<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 6/20/17 3:53 PM
 * @file: Get.php
 */

namespace Ebizmarts\MailChimp\Controller\Script;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Get extends Action
{
    /**
     * @var ResultFactory
     */
    private $_resultFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    private $_storeManager;

    /**
     * Get constructor.
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        $this->_resultFactory   = $context->getResultFactory();
        $this->_helper          = $helper;
        $this->_storeManager    = $storeManager;
        parent::__construct($context);
    }
    public function execute()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $url = $this->_helper->getJsUrl($storeId);
        $result = ['url'=>$url];
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);
        return $resultJson;
    }
}
