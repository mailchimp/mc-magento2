<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/12/17 11:35 AM
 * @file: Stores.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Ebizmarts\MailChimp\Model\MailChimpStoresFactory;

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
    protected $_helper;

    /**
     * Stores constructor.
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
        $this->_coreRegistry            = $registry;
        $this->_resultPageFactory       = $resultPageFactory;
        $this->_mailchimpStoresFactory  = $storesFactory;
        $this->_helper                  = $helper;
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
