<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/3/17 3:28 PM
 * @file: Getresponse.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Cron;

use Magento\Framework\Controller\ResultFactory;

class GetResponse extends \Magento\Backend\App\Action
{
    const MAX_RETRIES = 5;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Result
     */
    protected $_result;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_driver;

    /**
     * Getresponse constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Api\Result $result
     * @param \Magento\Framework\Filesystem\Driver\File $driver
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Result $result,
        \Magento\Framework\Filesystem\Driver\File $driver
    ) {
        parent::__construct($context);
        $this->_resultFactory       = $context->getResultFactory();
        $this->_result              = $result;
        $this->_helper              = $helper;
        $this->_driver              = $driver;
    }

    public function execute()
    {

    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::batch_grid');
    }
}
