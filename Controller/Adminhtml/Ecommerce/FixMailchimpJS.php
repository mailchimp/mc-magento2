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

use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\ValidatorException;
use Symfony\Component\Config\Definition\Exception\Exception;

class FixMailchimpJS extends \Magento\Backend\App\Action
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
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $config;
    protected $typeList;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $typeList
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Framework\App\Cache\TypeListInterface $typeList,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        parent::__construct($context);
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->helper               = $helper;
        $this->config               = $config;
        $this->typeList             = $typeList;
    }

    public function execute()
    {
        $valid = 1;
        $message = '';

        $params = $this->getRequest()->getParams();
        $this->helper->log($params);
        $scope = $params['scope'];
        $scopeId = $params['scopeId'];
        if ($scope == 'store') {
            $scope = 'stores';
        }
        $this->helper->log("Scope $scope id $scopeId");
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->config->deleteConfig(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL, $scope, $scopeId);
        } catch(ValidatorException $e) {
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
