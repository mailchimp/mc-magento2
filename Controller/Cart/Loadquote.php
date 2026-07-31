<?php
/**
 * Index
 *
 * @copyright Copyright © 2017 Ebizmarts Corp.. All rights reserved.
 * @author    info@ebizmarts.com
 */

namespace Ebizmarts\MailChimp\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Ebizmarts\MailChimp\Model\RedirectUrlValidator;

class Loadquote extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quote;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var \Magento\Framework\Url
     */
    protected $_urlHelper;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_message;
    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var RedirectUrlValidator
     */
    private $redirectUrlValidator;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param \Magento\Framework\Url $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param RedirectUrlValidator $redirectUrlValidator
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        \Magento\Framework\Url $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        RedirectUrlValidator $redirectUrlValidator
    ) {

        $this->pageFactory      = $pageFactory;
        $this->_quote           = $quote;
        $this->_customerSession = $customerSession;
        $this->_helper          = $helper;
        $this->syncHelper       = $syncHelper;
        $this->_urlHelper       = $urlHelper;
        $this->_message         = $context->getMessageManager();
        $this->_customerUrl     = $customerUrl;
        $this->_checkoutSession = $checkoutSession;
        $this->redirectUrlValidator = $redirectUrlValidator;
        parent::__construct($context);
    }

    /**
     * Index Action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->pageFactory->create();
        $params     = $this->getRequest()->getParams();
        if (isset($params['id'])) {
            $quote = $this->_quote->create();
            $quote->getResource()->load($quote, $params['id']);
            $magentoStoreId = $quote->getStoreId();
            $mailchimpStoreId = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $magentoStoreId
            );
            $syncCommerce = $this->syncHelper->getChimpSyncEcommerce(
                $mailchimpStoreId,
                $params['id'],
                \Ebizmarts\MailChimp\Helper\Data::IS_QUOTE
            );
            $storedToken = $syncCommerce->getMailchimpToken();
            if (!isset($params['token']) || empty($storedToken)
                || !hash_equals((string)$storedToken, (string)$params['token'])
            ) {
                // @error
                $this->_message->addErrorMessage(__("You can't access this cart"));
                $url = $this->_urlHelper->getUrl(
                    $this->_helper->getConfigValue(
                        \Ebizmarts\MailChimp\Helper\Data::XML_ABANDONEDCART_PAGE,
                        $magentoStoreId
                    )
                );
                $this->_redirect($this->redirectUrlValidator->getSafeUrl($url, $magentoStoreId));
            } else {
                if (isset($params['mc_cid'])) {
                    $url = $this->_urlHelper->getUrl(
                        $this->_helper->getConfigValue(
                            \Ebizmarts\MailChimp\Helper\Data::XML_ABANDONEDCART_PAGE,
                            $magentoStoreId
                        ),
                        ['mc_cid'=> $params['mc_cid']]
                    );
                    $quote->setData('mailchimp_campaign_id', $params['mc_cid']);
                } else {
                    $url = $this->_urlHelper->getUrl(
                        $this->_helper->getConfigValue(
                            \Ebizmarts\MailChimp\Helper\Data::XML_ABANDONEDCART_PAGE,
                            $magentoStoreId
                        )
                    );
                }
                $quote->setData('mailchimp_abandonedcart_flag', true);

                $quote->getResource()->save($quote);
                $this->_helper->sendCartEvent($quote);
                if (!$quote->getCustomerId()) {
                    $this->_checkoutSession->setQuoteId($quote->getId());
                    $this->_redirect($this->redirectUrlValidator->getSafeUrl($url, $magentoStoreId));
                } else {
                    if ($this->_customerSession->isLoggedIn()) {
                        $this->_redirect($this->redirectUrlValidator->getSafeUrl($url, $magentoStoreId));
                    } else {
                        $this->_message->addNoticeMessage(__("Login to complete your order"));
                        if (isset($params['mc_cid'])) {
                            $url = $this->_urlHelper->getUrl(
                                $this->_customerUrl->getLoginUrl(),
                                ['mc_cid'=>$params['mc_cid']]
                            );
                        } else {
                            $url = $this->_customerUrl->getLoginUrl();
                        }
                        $this->_redirect($this->redirectUrlValidator->getSafeUrl($url, $magentoStoreId));
                    }
                }
            }
        }
        return $resultPage;
    }
}
