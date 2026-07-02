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

namespace Ebizmarts\MailChimp\Block\Pixel;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\View\Element\Template;

/**
 * Emits the Pixel loader into <head>.
 *
 * WHY client-side consent (not server-side):
 *   If we gate the pixel script server-side inside a FPC-cacheable block,
 *   FPC picks the consent/no-consent bucket at cache-prime time and freezes
 *   it. Visitors who accept cookies mid-session hit a cache HIT with the old
 *   (no-pixel) HTML — the script never loads.
 *
 * The solution:
 *   1. Render ONE consent-NEUTRAL page for everyone (FPC caches a single copy).
 *   2. Emit a <script type="application/json" id="mc-pixel-config"> island
 *      with scriptUrl, websiteId, restrictionMode — data only, not executable.
 *   3. Load consent-loader.js via <script src> from our own domain (CSP-safe).
 *      That script reads the island and decides whether to inject the pixel
 *      by checking the real cookie value at runtime.
 */
class Script extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @param Template\Context $context
     * @param MailChimpHelper  $helper
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * Returns the JSON config island data for the consent-loader.
     *
     * @return array|null  null when pixel is not configured for this store
     */
    public function getPixelConfig(): ?array
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();

        if (!$this->helper->isPixelEnabled($storeId)) {
            return null;
        }

        $scriptUrl = $this->helper->getPixelScriptUrl($storeId);
        if (!$scriptUrl) {
            return null;
        }

        $restrictionMode = (bool)$this->_scopeConfig->getValue(
            'web/cookie/cookie_restriction',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return [
            'scriptUrl'       => $scriptUrl,
            'websiteId'       => $this->helper->getWebsiteId($storeId),
            'restrictionMode' => $restrictionMode,
            'currencyCode'    => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
        ];
    }

    /**
     * Skip rendering entirely when the pixel is not configured.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if ($this->getPixelConfig() === null) {
            return '';
        }
        return parent::_toHtml();
    }
}
