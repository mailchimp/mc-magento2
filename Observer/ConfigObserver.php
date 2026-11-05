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
use Ebizmarts\MailChimp\Model\Service\Pixel\NewConnectionQueue;
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
     * @var NewConnectionQueue
     */
    private $newConnectionQueue;

    /**
     * ConfigObserver constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param ConnectedSiteProvisioner $connectedSiteProvisioner
     * @param ReinitableConfigInterface $reinitableConfig
     * @param NewConnectionQueue $newConnectionQueue
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Registry $registry,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        ConnectedSiteProvisioner $connectedSiteProvisioner,
        ReinitableConfigInterface $reinitableConfig,
        NewConnectionQueue $newConnectionQueue
    ) {
        $this->_helper                  = $helper;
        $this->_storeManager            = $storeManager;
        $this->_registry                = $registry;
        $this->connectedSiteProvisioner = $connectedSiteProvisioner;
        $this->reinitableConfig         = $reinitableConfig;
        $this->newConnectionQueue       = $newConnectionQueue;
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
        // After the pass above, not before it. That pass re-reads the config,
        // so a store view enabled here first would be in front of it in this
        // same request -- and if Mailchimp returned no fragment, provisioned a
        // second time, without the bound-site-only rule.
        $this->provisionNewConnections();
    }

    /**
     * Turn the Pixel on for the store views this save connected.
     *
     * MonkeyStore::afterSave() decides which store views qualify -- a real
     * connection, at store-view scope, with no Pixel value chosen yet -- and
     * queues them, because it runs before the configuration is re-initialised
     * and would provision with the values from before the save. This runs on
     * the event Magento dispatches after the commit and the re-init, so the API
     * key, the Mailchimp store and the active flag read here are the ones the
     * merchant just saved.
     *
     * Only the connected site bound to the Mailchimp store is used: a
     * standalone one cannot attribute to the store, so on a host that already
     * has an attributing site the Pixel stays off and the provisioner logs why.
     *
     * A failure is logged and never fails the save. The merchant can still
     * turn the Pixel on by hand, which provisions again.
     *
     * @return void
     */
    private function provisionNewConnections(): void
    {
        foreach ($this->newConnectionQueue->drain() as $storeId) {
            try {
                // Checked again against the committed configuration: the
                // queue was filled before the save was known to have stuck.
                if (!$this->_helper->isStoreConnected($storeId)) {
                    continue;
                }

                $mailchimpStoreId = (string)$this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                    $storeId
                );
                $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);

                $this->connectedSiteProvisioner->provision(
                    (int)$storeId,
                    $mailchimpStoreId,
                    (string)parse_url($baseUrl, PHP_URL_HOST),
                    true
                );
            } catch (\Throwable $e) {
                // Deliberately \Throwable and deliberately swallowed: the
                // store is connected by now, and a Mailchimp outage, a revoked
                // key or an error anywhere in the provisioner must not turn
                // that save into an error page.
                $this->_helper->log(
                    'ConfigObserver: could not provision the Pixel for newly connected store '
                    . $storeId . ': ' . $e->getMessage()
                );
            }
        }
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
