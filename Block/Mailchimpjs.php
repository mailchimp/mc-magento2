<?php

namespace Ebizmarts\MailChimp\Block;

use Magento\Store\Model\ScopeInterface;

class Mailchimpjs extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $_secureHTtmlRender;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\View\Helper\SecureHtmlRenderer $secureHTtmlRender,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
        $this->_storeManager = $context->getStoreManager();
        $this->_secureHTtmlRender = $secureHTtmlRender;
    }

    public function getJsUrl()
    {
        $storeId = $this->_storeManager->getStore()->getId();

        $url = $this->_scopeConfig->getValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $active = $this->_scopeConfig->getValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        // if we have URL cached or integration is disabled
        // then avoid initialization of Mailchimp Helper and all linked classes (~30 classes)
        if ($active && !$url) {
            $url = $this->_helper->getJsUrl($storeId);
        }

        return $url;
    }

    public function getRender()
    {
        return $this->_secureHTtmlRender;
    }
}
