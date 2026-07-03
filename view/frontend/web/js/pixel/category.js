/**
 * Ebizmarts_MailChimp — PRODUCT_CATEGORY_VIEWED tracker.
 *
 * Self-contained IIFE. Reads the mailchimp-pixel-category-data island and fires
 * PRODUCT_CATEGORY_VIEWED unwrapped via the Mailchimp Pixel SDK.
 *
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
(function () {
    'use strict';
    var DATA_ID = 'mailchimp-pixel-category-data';
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

    var category = readIsland();
    if (!category || !category.categoryId) { return; }
    whenPixelReady(function () {
        try { window.$mcSite.pixel.api.track('PRODUCT_CATEGORY_VIEWED', category); } catch (e) {}
    });
}());
