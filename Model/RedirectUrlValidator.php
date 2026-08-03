<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file: RedirectUrlValidator.php
 */

namespace Ebizmarts\MailChimp\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Defense-in-depth guard against open redirects (CWE-601).
 *
 * A URL is considered safe only when it is relative (no host of its own) or
 * when its host matches one of the current store's own base URL hosts. Anything
 * else — an absolute URL to a foreign host, a protocol-relative URL
 * (//evil.com), a backslash bypass (/\evil.com) or a non-http(s) scheme
 * (javascript:, data:) — is rejected so the caller can fall back to a trusted
 * destination instead of forwarding the shopper off-site.
 */
class RedirectUrlValidator
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param string|null $url
     * @param int|string|null $storeId
     * @return bool
     */
    public function isSafe($url, $storeId = null)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return false;
        }

        // Normalize backslashes: browsers treat "\" as "/" so "/\evil.com" and
        // "https:\\evil.com" would otherwise slip past a naive parse_url check.
        $normalized = str_replace('\\', '/', $url);

        // Protocol-relative URL — no scheme but points to another host.
        if (strpos($normalized, '//') === 0) {
            return false;
        }

        $scheme = parse_url($normalized, PHP_URL_SCHEME);
        if ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        $host = parse_url($normalized, PHP_URL_HOST);
        if (empty($host)) {
            // Relative path with no host of its own — stays on the current site.
            return true;
        }

        return in_array(strtolower($host), $this->getAllowedHosts($storeId), true);
    }

    /**
     * Return $url when it is safe, otherwise a guaranteed-internal fallback
     * (the store's own base URL).
     *
     * @param string|null $url
     * @param int|string|null $storeId
     * @return string
     */
    public function getSafeUrl($url, $storeId = null)
    {
        if ($this->isSafe($url, $storeId)) {
            return (string)$url;
        }

        return $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
    }

    /**
     * Hosts that belong to the current store (secure and unsecure web base URLs).
     *
     * @param int|string|null $storeId
     * @return string[]
     */
    private function getAllowedHosts($storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $hosts = [];
        foreach ([true, false] as $secure) {
            $host = parse_url($store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $secure), PHP_URL_HOST);
            if (!empty($host)) {
                $hosts[] = strtolower($host);
            }
        }

        return array_unique($hosts);
    }
}
