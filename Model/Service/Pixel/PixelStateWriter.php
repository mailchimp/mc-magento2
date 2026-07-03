<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Service\Pixel;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Writes Pixel configuration in the correct order to avoid inconsistent state.
 *
 * Order matters:
 *   1. script_url
 *   2. script_fragment
 *   3. enabled_for_store = 1  ← LAST, so a failure saving the script never
 *      leaves the store "enabled but without a script".
 *
 * On disable: clear fragment + url, then set enabled_for_store = 0.
 */
class PixelStateWriter
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param WriterInterface   $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->configWriter  = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Enable the Pixel for a store, saving script data before the toggle.
     *
     * @param int    $storeId
     * @param string $scriptUrl
     * @param string $scriptFragment
     * @return void
     */
    public function enable(int $storeId, string $scriptUrl, string $scriptFragment): void
    {
        // 1. url — also keep the legacy mailchimpjsurl in sync so the Mailchimpjs
        // block (default.xml) loads the same script as the pixel consent-loader.
        // Without this, two different connected-site scripts load on every page and
        // the older one initialises the SDK with a stale mailchimpConnectedSiteId.
        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_SCRIPT_URL,
            $scriptUrl,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $this->configWriter->save(
            MailChimpHelper::XML_MAILCHIMP_JS_URL,
            $scriptUrl,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        // 2. fragment
        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT,
            $scriptFragment,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        // 3. toggle — always last
        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE,
            1,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->invalidateConfig();
    }

    /**
     * Disable the Pixel for a store, clearing script data before the toggle.
     *
     * @param int $storeId
     * @return void
     */
    public function disable(int $storeId): void
    {
        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_SCRIPT_FRAGMENT,
            '',
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_SCRIPT_URL,
            '',
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->configWriter->save(
            MailChimpHelper::XML_PIXEL_ENABLED_FOR_STORE,
            0,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        $this->invalidateConfig();
    }

    /**
     * Invalidate the config cache so changes take effect immediately.
     *
     * @return void
     */
    private function invalidateConfig(): void
    {
        $this->cacheTypeList->cleanType('config');
    }
}
