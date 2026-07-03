<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/23/17 12:22 PM
 * @file: ConfigObserver.php
 */

namespace Ebizmarts\MailChimp\Observer;

use Ebizmarts\MailChimp\Model\Service\Admin\ConnectedSiteProvisioner;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\UrlInterface;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var ConnectedSiteProvisioner
     */
    private $connectedSiteProvisioner;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * ConfigObserver constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param ConnectedSiteProvisioner $connectedSiteProvisioner
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Registry $registry,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        ConnectedSiteProvisioner $connectedSiteProvisioner,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->_helper                  = $helper;
        $this->_storeManager            = $storeManager;
        $this->_registry                = $registry;
        $this->connectedSiteProvisioner = $connectedSiteProvisioner;
        $this->reinitableConfig         = $reinitableConfig;
    }

    public function execute(EventObserver $observer)
    {
        $oldListId  = $this->_registry->registry('oldListId');
        $apiKey     = $this->_registry->registry('apiKey');
        $mustDelete = true;

        foreach ($this->_storeManager->getStores() as $storeId => $val) {
            $listId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId);
            if ($listId == $oldListId) {
                $mustDelete = false;
            }
        }
        if ($mustDelete) {
            $this->_helper->deleteWebHook($apiKey, $oldListId);
        }

        $this->_registry->unregister('oldListId');
        $this->_registry->unregister('apiKey');

        $this->provisionPixelIfNeeded();
    }

    /**
     * For each store where the pixel toggle was just enabled but the script
     * fragment has not yet been saved, call the provisioner to retrieve the
     * script URL/fragment from Mailchimp and persist them.
     */
    private function provisionPixelIfNeeded(): void
    {
        // Reinitialize config so we read the values the admin just saved.
        $this->reinitableConfig->reinit();

        foreach ($this->_storeManager->getStores() as $storeId => $store) {
            $mailchimpStoreId = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                $storeId
            );

            if (!$mailchimpStoreId) {
                continue;
            }

            $pixelEnabled = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PIXEL_ENABLED_FOR_STORE,
                $storeId
            );

            if (!$pixelEnabled) {
                continue;
            }

            $scriptFragment = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PIXEL_SCRIPT_FRAGMENT,
                $storeId
            );
            $scriptUrl = $this->_helper->getConfigValue(
                \Ebizmarts\MailChimp\Helper\Data::XML_PIXEL_SCRIPT_URL,
                $storeId
            );

            // Skip only when fully provisioned (both fragment and URL are saved).
            if ($scriptFragment && $scriptUrl) {
                continue;
            }

            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            $domain  = parse_url($baseUrl, PHP_URL_HOST);

            try {
                $this->connectedSiteProvisioner->provision((int)$storeId, $mailchimpStoreId, $domain);
            } catch (\Exception $e) {
                $this->_helper->log(
                    'ConfigObserver: pixel provisioning failed for store ' . $storeId . ': ' . $e->getMessage()
                );
            }
        }
    }
}
