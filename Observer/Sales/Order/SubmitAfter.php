<?php

namespace Ebizmarts\MailChimp\Observer\Sales\Order;

class SubmitAfter implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    /**
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $metadataFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $metadataFactory,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
        $this->_cookieManager = $cookieManager;
        $this->_helper = $helper;
        $this->_cookieMetadataFactory = $metadataFactory;
        $this->_sessionManager = $sessionManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $this->_cookieManager->deleteCookie(
                'mailchimp_campaign_id',
                $this->_cookieMetadataFactory
                    ->createCookieMetadata()
                    ->setPath($this->_sessionManager->getCookiePath())
                    ->setDomain($this->_sessionManager->getCookieDomain())
            );
            $this->_cookieManager->deleteCookie(
                'mailchimp_landing_page',
                $this->_cookieMetadataFactory
                    ->createCookieMetadata()
                    ->setPath($this->_sessionManager->getCookiePath())
                    ->setDomain($this->_sessionManager->getCookieDomain())
            );
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }

        return $this;
    }
}
