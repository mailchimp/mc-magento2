<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 2/21/17 5:07 PM
 * @file: ResetLocalErrors.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Magento\Framework\Controller\Result\JsonFactory;

class ResetLocalErrors extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper               = $helper;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'valid' => (int)1,
            'message' => 'OK',
        ]);
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_EncryptionKey::crypt_key');
    }
}