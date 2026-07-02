/**
 * Ebizmarts_MailChimp — CHECKOUT_STARTED tracker.
 *
 * Self-contained IIFE. Reads the mailchimp-pixel-checkout-data island and fires
 * CHECKOUT_STARTED via the Mailchimp Pixel SDK. Deduplicates per cartId using
 * sessionStorage.
 *
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
(function () {
    'use strict';
    var DATA_ID = 'mailchimp-pixel-checkout-data';
    var DEDUP_PREFIX = 'mc_pixel_checkout_started_';
    var POLL_INTERVAL_MS = 100;
    var POLL_MAX_ATTEMPTS = 100;
    var POST_READY_GRACE_MS = 1000;

    function isPixelReady() {
        return !!(window.$mcSite && window.$mcSite.pixel && window.$mcSite.pixel.installed === true
            && window.$mcSite.pixel.api && typeof window.$mcSite.pixel.api.track === 'function');
    }

    function whenPixelReady(cb) {
        var fire = function () { setTimeout(cb, POST_READY_GRACE_MS); };
        if (isPixelReady()) { fire(); return; }
        var attempts = 0;
        var iv = setInterval(function () {
            attempts++;
            if (isPixelReady()) { clearInterval(iv); fire(); } else if (attempts > POLL_MAX_ATTEMPTS) { clearInterval(iv); }
        }, POLL_INTERVAL_MS);
    }

    function readIsland() {
        var el = document.getElementById(DATA_ID);
        if (!el) { return null; }
        try { return JSON.parse(el.textContent || el.innerHTML); } catch (e) { return null; }
    }

    var checkout = readIsland();
    if (!checkout || !checkout.id) { return; }
    var dedupKey = DEDUP_PREFIX + checkout.cartId;
    try { if (sessionStorage.getItem(dedupKey)) { return; } } catch (e) {}
    whenPixelReady(function () {
        try {
            window.$mcSite.pixel.api.track('CHECKOUT_STARTED', { checkout: checkout });
            try { sessionStorage.setItem(dedupKey, '1'); } catch (e) {}
        } catch (e) {}
    });
}());
