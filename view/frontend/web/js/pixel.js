/**
 * Ebizmarts_MailChimp — PRODUCT_ADDED_TO_CART cart watcher.
 *
 * Self-contained IIFE loaded on every storefront page via default.xml
 * (<script src defer>). Watches Magento's customer-data 'cart' section
 * for quantity increases and fires PRODUCT_ADDED_TO_CART via the SDK.
 *
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
(function () {
    'use strict';
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

    function safeTrack(event, data) {
        whenPixelReady(function () {
            try { window.$mcSite.pixel.api.track(event, data); } catch (e) {}
        });
    }

    function snapshot(data) {
        var items = (data && data.items) || [];
        var snap = {};
        items.forEach(function (it) {
            var key = String(it.item_id || it.product_id || it.product_sku || it.id || '');
            if (!key) { return; }
            snap[key] = { qty: parseFloat(it.qty) || 0, item: it };
        });
        return snap;
    }

    function fireAddedToCart(item, addedQty, cartId, currency) {
        var unitPrice = parseFloat(
            item.product_price_value !== undefined ? item.product_price_value : item.price
        ) || 0;
        var imageUrl = (item.product_image && item.product_image.src) ? item.product_image.src : '';
        var productId = String(item.product_id || item.item_id || '');
        // Mailchimp variant identity. mc_variant_id is attached server side by
        // Ebizmarts\MailChimp\Model\Plugin\CustomerData\CartItemVariantId, because the
        // customer-data cart section only carries the PARENT id for a configurable
        // and the chosen child cannot be resolved in the browser.
        var variantId = String(item.mc_variant_id || '') || productId;
        var product = {
            item: {
                id: variantId, productId: productId,
                title: item.product_name || item.name || '',
                price: unitPrice, currency: currency || '',
                sku: String(item.product_sku || item.sku || ''),
                imageUrl: imageUrl,
                productUrl: String(item.product_url || ''),
                vendor: '', categories: []
            },
            quantity: addedQty,
            price: unitPrice * addedQty,
            currency: currency || ''
        };
        var payload = { product: product };
        if (cartId) { payload.cartId = String(cartId); }
        safeTrack('PRODUCT_ADDED_TO_CART', payload);
    }

    function hookAddToCart() {
        require(['Magento_Customer/js/customer-data'], function (customerData) {
            var cart = customerData.get('cart');
            var initialized = false;
            var prevSnap = {};
            cart.subscribe(function (data) {
                var curr = snapshot(data);
                if (!initialized) { prevSnap = curr; initialized = true; return; }
                var cartId = (data && (data.cart_id || data.cartId || data.guestCartId || data.quoteId)) || '';
                var currency = (data && (data.currency_code || data.currency)) || '';
                if (!currency) {
                    try {
                        var cfg = JSON.parse(document.getElementById('mc-pixel-config').textContent);
                        currency = cfg.currencyCode || '';
                    } catch (e) {}
                }
                Object.keys(curr).forEach(function (key) {
                    var prevQty = (prevSnap[key] && prevSnap[key].qty) || 0;
                    if (curr[key].qty > prevQty) {
                        fireAddedToCart(curr[key].item, curr[key].qty - prevQty, cartId, currency);
                    }
                });
                prevSnap = curr;
            });
            prevSnap = snapshot(cart());
            initialized = true;
        });
    }

    hookAddToCart();
}());
