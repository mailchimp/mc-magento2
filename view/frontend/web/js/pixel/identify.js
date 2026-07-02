/**
 * Ebizmarts_MailChimp — Visitor identify handler.
 *
 * Self-contained IIFE loaded on every storefront page. Handles all identify
 * paths: logged-in customer island, checkout email field blur/input debounce,
 * and MutationObserver detection for fields rendered late by JS frameworks.
 * SHA-256 hashing via crypto.subtle when available, falls back to plain EMAIL.
 * Exposes window.EbizMcPixel.identify for external callers.
 *
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
(function () {
    'use strict';
    var POLL_INTERVAL_MS = 100;
    var POLL_MAX_ATTEMPTS = 100;
    var POST_READY_GRACE_MS = 1000;
    var INPUT_DEBOUNCE_MS = 1500;
    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var firedEmails = {};

    function isCompleteEmail(v) {
        return typeof v === 'string' && EMAIL_RE.test(v);
    }

    function isPixelReady() {
        return !!(window.$mcSite && window.$mcSite.pixel && window.$mcSite.pixel.installed === true
            && window.$mcSite.pixel.api && typeof window.$mcSite.pixel.api.identify === 'function');
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

    function sha256Hex(str) {
        if (!window.crypto || !window.crypto.subtle || typeof TextEncoder === 'undefined') {
            return Promise.reject(new Error('unavailable'));
        }
        var buf = new TextEncoder().encode(str);
        return window.crypto.subtle.digest('SHA-256', buf).then(function (hashBuf) {
            return Array.from(new Uint8Array(hashBuf))
                .map(function (b) { return ('00' + b.toString(16)).slice(-2); })
                .join('');
        });
    }

    function identify(rawEmail) {
        if (!rawEmail || typeof rawEmail !== 'string') { return; }
        var email = rawEmail.toLowerCase().trim();
        if (!isCompleteEmail(email)) { return; }
        if (firedEmails[email]) { return; }
        firedEmails[email] = true;
        whenPixelReady(function () {
            try {
                sha256Hex(email).then(function (hex) {
                    window.$mcSite.pixel.api.identify({ type: 'EMAIL_SHA256', value: hex });
                }).catch(function () {
                    window.$mcSite.pixel.api.identify({ type: 'EMAIL', value: email });
                });
            } catch (e) {
                try { window.$mcSite.pixel.api.identify({ type: 'EMAIL', value: email }); } catch (ee) {}
            }
        });
    }

    function readIdentityIsland() {
        var el = document.getElementById('mailchimp-pixel-identity-data');
        if (!el) { return; }
        try {
            var parsed = JSON.parse(el.textContent || el.innerHTML);
            if (parsed && parsed.email) { identify(parsed.email); }
        } catch (e) {}
    }

    function hookCheckoutEmailField() {
        var SELECTORS = ['#customer-email', 'input[type="email"][name="email_address"]'];
        var attached = null;

        function findField() {
            for (var i = 0; i < SELECTORS.length; i++) {
                var el = document.querySelector(SELECTORS[i]);
                if (el) { return el; }
            }
            return null;
        }

        function attach(field) {
            if (attached && attached.field === field) { return; }
            if (attached) { attached.teardown(); }
            var lastValue = '';
            var inputTimer = null;
            var onBlur = function () {
                if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
                var v = field.value || '';
                if (v && v !== lastValue) { lastValue = v; identify(v); }
            };
            var onInput = function () {
                if (inputTimer) { clearTimeout(inputTimer); }
                inputTimer = setTimeout(function () {
                    inputTimer = null;
                    var v = field.value || '';
                    if (v && v !== lastValue) { lastValue = v; identify(v); }
                }, INPUT_DEBOUNCE_MS);
            };
            field.addEventListener('blur', onBlur);
            field.addEventListener('input', onInput);
            attached = {
                field: field,
                teardown: function () {
                    if (inputTimer) { clearTimeout(inputTimer); inputTimer = null; }
                    field.removeEventListener('blur', onBlur);
                    field.removeEventListener('input', onInput);
                    attached = null;
                }
            };
        }

        var existing = findField();
        if (existing) { attach(existing); }

        if (typeof MutationObserver === 'function') {
            var obs = new MutationObserver(function () {
                var f = findField();
                if (f) { attach(f); } else if (attached) { attached.teardown(); }
            });
            obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
        } else {
            var attempts = 0;
            var iv = setInterval(function () {
                attempts++;
                var f = findField();
                if (f) { attach(f); }
                if (attempts > POLL_MAX_ATTEMPTS) { clearInterval(iv); }
            }, POLL_INTERVAL_MS);
        }
    }

    window.EbizMcPixel = window.EbizMcPixel || {};
    window.EbizMcPixel.identify = identify;
    readIdentityIsland();
    hookCheckoutEmailField();
}());
