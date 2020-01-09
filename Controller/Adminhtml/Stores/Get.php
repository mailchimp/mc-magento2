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
namespace Ebizmarts\MailChimp\Controller\Adminhtml\Stores;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Get extends Action
{
    const MAX_STORES = 200;

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
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {

        parent::__construct($context);
        $this->_resultFactory       = $context->getResultFactory();
        $this->_helper                  = $helper;
    }
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $apiKey = $param['apikey'];
        $encrypt = $param['encrypt'];
        try {
            $api = $this->_helper->getApiByApiKey($apiKey, $encrypt);
            $stores = $api->ecommerce->stores->get(null, null, null, self::MAX_STORES);
            $result = [];
            $result['valid'] = 1;
            $result['stores'] = [];
            foreach ($stores['stores'] as $store) {
                if ($store['platform'] == \Ebizmarts\MailChimp\Helper\Data::PLATFORM) {
                    if ($store['list_id']=='') {
                        continue;
                    }
                    $list = $api->lists->getLists($store['list_id']);
                    $result['stores'][] = [
                        'id' => $store['id'],
                        'name' => $store['name'],
                        'list_name' => $list['name'],
                        'list_id' => $store['list_id']
                    ];
                    $result['valid'] = 1;
                }
            }
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
            $result['valid'] = 0;
            $result['errormsg'] = $e->getTitle();
        }
        $resultJson = $this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);
        return $resultJson;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }
}
