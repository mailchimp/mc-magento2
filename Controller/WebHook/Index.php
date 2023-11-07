<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/23/17 3:36 PM
 * @file: Index.php
 */

namespace Ebizmarts\MailChimp\Controller\WebHook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends Action
{
    const WEBHOOK__PATH = 'mailchimp/webhook/index';
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpWebhookRequestFactory
     */
    protected $_chimpWebhookRequestFactory;
    private $_remoteAddress;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpWebhookRequestFactory $chimpWebhookRequestFactory
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     */
    public function __construct(
        Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpWebhookRequestFactory $chimpWebhookRequestFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
    
        parent::__construct($context);
        $this->_resultFactory              = $context->getResultFactory();
        $this->_helper                      = $helper;
        $this->_chimpWebhookRequestFactory  = $chimpWebhookRequestFactory;
        $this->_remoteAddress               = $remoteAddress;
    }

    public function execute()
    {
        $requestKey = $this->getRequest()->getParam('wkey');
        if (!$requestKey) {
            $this->_helper->log('No wkey parameter from ip: '.$this->_remoteAddress->getRemoteAddress());
            $result = $this->_resultFactory->create(ResultFactory::TYPE_RAW)->setHttpResponseCode(403);
            return $result;
        }
        $key = $this->_helper->getWebhooksKey();
        if ($key!=$requestKey) {
            $this->_helper->log('wkey parameter is invalid from ip: '.$this->_remoteAddress->getRemoteAddress());
            $result = $this->_resultFactory->create(ResultFactory::TYPE_RAW)->setHttpResponseCode(403);
            return $result;
        }
        if ($this->getRequest()->getPost('type')) {
            $request = $this->getRequest()->getPost();
            if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_WEBHOOK_ACTIVE) ||
                $request['type']==\Ebizmarts\MailChimp\Cron\Webhook::TYPE_SUBSCRIBE) {
                try {
                    $chimpRequest = $this->_chimpWebhookRequestFactory->create();
                    $chimpRequest->setType($request['type']);
                    $chimpRequest->setFiredAt($request['fired_at']);
                    $chimpRequest->setDataRequest($this->_helper->serialize($request['data']));
                    $chimpRequest->setProcessed(false);
                    $chimpRequest->getResource()->save($chimpRequest);
                } catch(\Exception $e) {
                    $this->_helper->log($e->getMessage());
                    $this->_helper->log($request['data']);
                }
            } else {
                $this->_helper->log("The two way is off");
            }
        } else {
            $this->_helper->log('An empty request comes from ip: '.$this->_remoteAddress->getRemoteAddress());
            $result = $this->_resultFactory->create(ResultFactory::TYPE_RAW)->setHttpResponseCode(200);
            return $result;
        }
    }
}
