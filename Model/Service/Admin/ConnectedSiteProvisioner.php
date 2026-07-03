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

namespace Ebizmarts\MailChimp\Model\Service\Admin;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Service\Pixel\PixelStateWriter;
use Magento\Framework\Exception\LocalizedException;

/**
 * Provisions the Mailchimp Pixel for a Magento store.
 *
 * Strategy (revised):
 *   The Mailchimp ecommerce store already has a connected site bound to it
 *   (visible via GET /ecommerce/stores/{id} → connected_site).  That site's
 *   script URL is what the browser loads (saved in mailchimp/general/mailchimpjsurl)
 *   and its ID is what the Pixel SDK reports in every tracking event.
 *   We must call enable-pixel on THAT site, not on a new one we create.
 *
 *   Flow:
 *   1. GET /ecommerce/stores/{mailchimpStoreId}
 *      → read connected_site.site_foreign_id, site_script.url, site_script.fragment
 *      If the store already has a bound connected site → use it (preferred path).
 *
 *   2. POST /connected-sites { foreign_id, domain }  (only when no bound site)
 *      → parse foreign_id + site_script.url from response.
 *      "Already exists" error → fall back to GET /connected-sites/{foreign_id}.
 *
 *   3. POST /connected-sites/{foreignId}/actions/enable-pixel
 *      Always called as long as foreignId is known.
 *
 *   4. Persist via PixelStateWriter (url → fragment → enabled_for_store=1).
 *      Skipped when no new scriptUrl was retrieved (keeps existing DB value).
 *
 * Binding semantics (learned in production):
 *   A connected-site binds to a store only via the auto-nest on creation when
 *   the host is free. A standalone POST cannot bind store_id afterwards.
 *   => 1 host = 1 attributing pixel. A second store on the same host gets a
 *      dead pixel (storeId:0 / CUSTOM). Per-store attribution requires
 *      per-store hostnames.
 *
 * NOTE on API client:
 *   The ebizmarts/mailchimp-lib does not have a connectedSites sub-client.
 *   All calls go through $api->call($path, $params, $method) directly.
 */
class ConnectedSiteProvisioner
{
    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @var PixelStateWriter
     */
    private $pixelStateWriter;

    /**
     * @param MailChimpHelper  $helper
     * @param PixelStateWriter $pixelStateWriter
     */
    public function __construct(
        MailChimpHelper $helper,
        PixelStateWriter $pixelStateWriter
    ) {
        $this->helper           = $helper;
        $this->pixelStateWriter = $pixelStateWriter;
    }

    /**
     * Provision the Pixel for the given Magento store.
     *
     * @param int    $storeId          Magento store ID
     * @param string $mailchimpStoreId Mailchimp ecommerce store ID
     * @param string $domain           Store domain (e.g. "example.com")
     * @return void
     * @throws LocalizedException
     */
    public function provision(int $storeId, string $mailchimpStoreId, string $domain): void
    {
        $api = $this->helper->getApi($storeId);

        // ── Step 1: resolve foreignId, scriptUrl, fragment ───────────────────
        //
        // Preferred: use the connected site already bound to the ecommerce store.
        // Its foreign_id is what the Pixel SDK will report as mailchimpConnectedSiteId,
        // so enable-pixel MUST be called on that same site.

        $foreignId      = null;
        $scriptUrl      = null;
        $scriptFragment = '';

        [$foreignId, $scriptUrl, $scriptFragment] = $this->resolveFromEcommerceStore($api, $mailchimpStoreId);

        // Fallback: create (or retrieve) a connected site manually.
        if (!$foreignId) {
            [$foreignId, $scriptUrl] = $this->resolveFromConnectedSites($api, $mailchimpStoreId, $domain);
        }

        if (!$foreignId) {
            throw new LocalizedException(
                __('Unable to determine connected site for store %1.', $storeId)
            );
        }

        // ── Step 2: enable pixel ──────────────────────────────────────────────
        //
        // Must be called on the SAME foreignId the SDK reports, otherwise the
        // /pixel/v1/track endpoint returns 400 Invalid request data.
        try {
            $api->call('/connected-sites/' . $foreignId . '/actions/enable-pixel', [], \Mailchimp::POST);
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->helper->log('ConnectedSiteProvisioner: enable-pixel failed: ' . $e->getMessage());
        }

        // ── Step 3: persist ───────────────────────────────────────────────────
        // If the API did not return a URL directly, try to extract it from the
        // fragment (e.g. src="https://chimpstatic.com/...").
        if (!$scriptUrl && $scriptFragment) {
            if (preg_match('/src=["\']([^"\']+)["\']/', $scriptFragment, $m)) {
                $scriptUrl = $m[1];
                $this->helper->log('ConnectedSiteProvisioner: script_url extracted from fragment: ' . $scriptUrl);
            }
        }

        if (!$scriptUrl) {
            $this->helper->log(
                'ConnectedSiteProvisioner: no script_url for store ' . $storeId .
                ' — keeping existing DB value. enable-pixel was still called.'
            );
            return;
        }

        // If fragment was not obtained from the ecommerce store, try the connected site directly.
        if (!$scriptFragment) {
            try {
                $site           = $api->call('/connected-sites/' . $foreignId, [], \Mailchimp::GET);
                $scriptFragment = $site['site_script']['fragment'] ?? '';
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                $this->helper->log('ConnectedSiteProvisioner: GET /connected-sites fragment fallback failed: ' . $e->getMessage());
            }
        }

        $this->pixelStateWriter->enable($storeId, $scriptUrl, $scriptFragment);
    }

    /**
     * Try to read the connected site already bound to the Mailchimp ecommerce store.
     *
     * Returns [foreignId, scriptUrl, fragment] or [null, null, ''] when unavailable.
     *
     * @param object $api
     * @param string $mailchimpStoreId
     * @return array{0: string|null, 1: string|null, 2: string}
     */
    private function resolveFromEcommerceStore($api, string $mailchimpStoreId): array
    {
        try {
            $storeData = $api->ecommerce->stores->get($mailchimpStoreId);
            $foreignId = $storeData['connected_site']['site_foreign_id'] ?? null;
            $scriptUrl = $storeData['connected_site']['site_script']['url'] ?? null;
            $fragment  = $storeData['connected_site']['site_script']['fragment'] ?? '';

            if ($foreignId) {
                return [$foreignId, $scriptUrl, $fragment];
            }
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->helper->log('ConnectedSiteProvisioner: GET /ecommerce/stores failed: ' . $e->getMessage());
        }

        return [null, null, ''];
    }

    /**
     * Create (or retrieve) a connected site via the /connected-sites endpoint.
     *
     * Returns [foreignId, scriptUrl] or [null, null] when unavailable.
     *
     * @param object $api
     * @param string $mailchimpStoreId
     * @param string $domain
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveFromConnectedSites($api, string $mailchimpStoreId, string $domain): array
    {
        $foreignId = null;
        $scriptUrl = null;

        try {
            $response  = $api->call('/connected-sites', [
                'foreign_id' => $mailchimpStoreId,
                'domain'     => $domain,
            ], \Mailchimp::POST);
            $foreignId = $response['foreign_id'] ?? null;
            $scriptUrl = $response['site_script']['url'] ?? null;
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->helper->log('ConnectedSiteProvisioner: POST /connected-sites failed: ' . $e->getMessage());
        }

        // POST failed (e.g. "already exists") — use mailchimpStoreId as foreign_id
        // since that is what we passed on creation.
        if (!$foreignId) {
            $foreignId = $mailchimpStoreId;
        }

        if (!$scriptUrl) {
            try {
                $site      = $api->call('/connected-sites/' . $foreignId, [], \Mailchimp::GET);
                $scriptUrl = $site['site_script']['url'] ?? null;
            } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                $this->helper->log(
                    'ConnectedSiteProvisioner: GET /connected-sites/' . $foreignId . ' failed: ' . $e->getMessage()
                );
            }
        }

        return [$foreignId ?: null, $scriptUrl];
    }
}
