<?php

namespace Ebizmarts\MailChimp\Controller\Adminhtml;

use Ebizmarts\MailChimp\Model\MailChimpStoresFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Stores extends Action
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
    /**
     * @var MailChimpStoresFactory
     */
    protected $_mailchimpStoresFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_mhelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     * @param MailChimpStoresFactory $storesFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PageFactory $resultPageFactory,
        MailChimpStoresFactory $storesFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $registry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_mailchimpStoresFactory = $storesFactory;
        $this->_mhelper = $helper;
    }

    public function execute()
    {
        return 1;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::stores_grid');
    }
}
