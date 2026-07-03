/**
 * Ebizmarts_MailChimp — Mailchimp Pixel consent loader
 *
 * WHY this exists (FPC-neutrality problem):
 *   If we gate the <script id="mcjs"> server-side inside a FPC-cacheable block,
 *   FPC freezes the consent/no-consent decision at cache-prime time. Visitors
 *   who accept cookies mid-session hit a cache HIT with the old (no-pixel)
 *   HTML — the script never loads for them.
 *
 * The solution (this file):
 *   1. PHP renders ONE consent-NEUTRAL page for all visitors.
 *      FPC stores a single copy — no bucket poisoning.
 *   2. This script reads runtime config from the JSON island
 *      <script id="mc-pixel-config" type="application/json">.
 *   3. Consent is evaluated HERE, in the browser, against the real cookie.
 *   4. If allowed: inject <script id="mcjs" src="…" async> into <head>
 *      (idempotent — won't double-inject).
 *   5. If not yet allowed: poll every ~1 s (up to ~30 min / 1800 tries)
 *      for late-consent (covers CMPs whose banners set the cookie without
 *      reloading the page).
 *
 * Consent logic mirrors Magento\Cookie\Helper\Cookie::isUserNotAllowSaveCookie():
 *   • restrictionMode === false  → banner OFF → consent not required → ALLOWED
 *   • otherwise: read cookie "user_allowed_save_cookie" (JSON keyed by website
 *     id, e.g. {"1":1}).
 *     ALLOWED only if the key for this website id EXISTS AND is truthy
 *     (mirrors PHP empty(): 0, "0", "", false, null = NOT consented).
 *     Cookie absent or parse error → NOT allowed (fail-safe).
 *
 * Loaded via <script src="…/js/pixel/consent-loader.js"> from script.phtml —
 * served from our own domain, always CSP-whitelisted.
 */
(function () {
    'use strict';

    var POLL_INTERVAL_MS  = 1000;
    var POLL_MAX_ATTEMPTS = 1800; // ~30 minutes
    var MCJS_SCRIPT_ID    = 'mcjs';
    var CONFIG_ISLAND_ID  = 'mc-pixel-config';

    // -------------------------------------------------------------------------
    // Config island
    // -------------------------------------------------------------------------

    function readConfig() {
        var el = document.getElementById(CONFIG_ISLAND_ID);
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent || el.innerHTML);
        } catch (e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Consent check
    // Mirrors Magento\Cookie\Helper\Cookie::isUserNotAllowSaveCookie()
    // -------------------------------------------------------------------------

    function isConsentGranted(config) {
        // restrictionMode OFF → opt-out model → always allowed
        if (!config.restrictionMode) {
            return true;
        }

        var raw = getCookieValue('user_allowed_save_cookie');
        if (!raw) {
            return false; // cookie absent → fail-safe
        }

        var parsed;
        try {
            parsed = JSON.parse(decodeURIComponent(raw));
        } catch (e) {
            return false; // parse error → fail-safe
        }

        if (typeof parsed !== 'object' || parsed === null) {
            return false;
        }

        var websiteId = String(config.websiteId);

        // Key must exist AND be truthy (mirrors PHP empty())
        if (!Object.prototype.hasOwnProperty.call(parsed, websiteId)) {
            return false; // key absent → not consented
        }

        var val = parsed[websiteId];
        // PHP empty(): 0, "0", "", false, null, [] → falsy
        return !!val && val !== '0' && val !== '';
    }

    // -------------------------------------------------------------------------
    // Cookie reader
    // -------------------------------------------------------------------------

    function getCookieValue(name) {
        var match = document.cookie.match(
            new RegExp('(?:^|;\\s*)' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)')
        );
        return match ? match[1] : null;
    }

    // -------------------------------------------------------------------------
    // Pixel injector (idempotent)
    // -------------------------------------------------------------------------

    function injectPixel(scriptUrl) {
        if (document.getElementById(MCJS_SCRIPT_ID)) {
            return; // already injected
        }
        var s = document.createElement('script');
        s.id    = MCJS_SCRIPT_ID;
        s.src   = scriptUrl;
        s.async = true;
        (document.head || document.documentElement).appendChild(s);
    }

    // -------------------------------------------------------------------------
    // Main
    // -------------------------------------------------------------------------

    var config = readConfig();
    if (!config || !config.scriptUrl) {
        return; // pixel not configured for this store
    }

    if (isConsentGranted(config)) {
        injectPixel(config.scriptUrl);
        return;
    }

    // Late-consent poll: covers CMPs that set the cookie without a page reload
    var attempts  = 0;
    var pollTimer = setInterval(function () {
        attempts++;
        if (isConsentGranted(config)) {
            clearInterval(pollTimer);
            injectPixel(config.scriptUrl);
        } else if (attempts >= POLL_MAX_ATTEMPTS) {
            clearInterval(pollTimer);
        }
    }, POLL_INTERVAL_MS);

}());
