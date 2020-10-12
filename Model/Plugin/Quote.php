<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/25/17 7:40 PM
 * @file: Quote.php
 */
namespace Ebizmarts\MailChimp\Model\Plugin;

class Quote
{
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;

    /**
     * Quote constructor.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        $this->_cookieManager = $cookieManager;
        $this->_helper  = $helper;
    }

    public function beforeBeforeSave(\Magento\Quote\Model\Quote $quote)
    {
        $mailchimp_campaign_id = $this->_cookieManager->getCookie('mailchimp_campaign_id');
        if ($mailchimp_campaign_id) {
            $quote->setData('mailchimp_campaign_id', $mailchimp_campaign_id);
        }
        $mailchimp_landing_page = $this->_cookieManager->getCookie('mailchimp_landing_page');
        if ($mailchimp_landing_page) {
            $quote->setData('mailchimp_landing_page', $mailchimp_landing_page);
        }
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $quote->getStoreId()
        );
        $this->_helper->saveEcommerceData(
            $mailchimpStoreId,
            $quote->getId(),
            \Ebizmarts\MailChimp\Helper\Data::IS_QUOTE,
            null,
            0,
            1,
            0,
            null
        );
    }
}
