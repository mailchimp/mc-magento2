<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/8/17 12:00 PM
 * @file: Mailchimpjs.php
 */

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
     * Mailchimpjs constructor.
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
        $this->_helper          = $helper;
        $this->_storeManager    = $context->getStoreManager();
        $this->_secureHTtmlRender   = $secureHTtmlRender;
    }

    public function getJsUrl()
    {
        $storeId = $this->_storeManager->getStore()->getId();

        $url = $this->_scopeConfig->getValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_JS_URL, ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $active = $this->_scopeConfig->getValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORES,
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
