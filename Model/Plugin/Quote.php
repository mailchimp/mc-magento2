<?php

namespace Ebizmarts\MailChimp\Model\Plugin;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

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
     * @var SyncHelper
     */
    private $syncHelper;

    /**
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper
    ) {
        $this->_cookieManager = $cookieManager;
        $this->_helper = $helper;
        $this->syncHelper = $syncHelper;
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
        $this->syncHelper->saveEcommerceData(
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
