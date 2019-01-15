<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/26/17 12:36 PM
 * @file: SubmitAfter.php
 */
namespace Ebizmarts\MailChimp\Observer\Sales\Order;

use Magento\Framework\Event\Observer;

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
     * SubmitAfter constructor.
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
    
        $this->_cookieManager   = $cookieManager;
        $this->_helper          = $helper;
        $this->_cookieMetadataFactory   = $metadataFactory;
        $this->_sessionManager          = $sessionManager;
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
