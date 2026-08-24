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

namespace Ebizmarts\MailChimp\Model\Product;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Resolves the LEAF product id a cart/order line actually references — the
 * child simple for a configurable, the product itself for every other type.
 *
 * Mailchimp's ProductVariantDto needs two different identifiers: `id` is the
 * variant the shopper selected, `productId` is the parent catalog product.
 * The pixel used to send the parent in both, so a configurable's variant never
 * joined against what Mailchimp already stores.
 *
 * The value returned here is not invented: this extension's own REST sync
 * (Model/Api/Product.php) already publishes the child simple's entity id as the
 * Mailchimp variant id, so the pixel has to send that same value or the join
 * fails.
 *
 * Only CONFIGURABLE products have a leaf distinct from themselves. Simple,
 * bundle and grouped are self-referencing in the REST variant payload, so
 * returning the product's own id for them is not a fallback — it is the
 * correct answer, and the only value that joins.
 *
 * Stateless, zero constructor dependencies: no DI cycle risk, no di.xml entry.
 */
class LeafProductIdResolver
{
    /**
     * Leaf id for a quote line.
     *
     * Falls back to the parent id when the child cannot be resolved (broken or
     * partial quote data) rather than returning null, so the degraded path is
     * exactly today's payload and never a dropped line.
     *
     * @param  QuoteItem $item
     * @return int
     */
    public function forQuoteItem(QuoteItem $item)
    {
        $productId = (int)$item->getProductId();

        if ($item->getProductType() !== 'configurable') {
            return $productId;
        }

        $simpleOption = $item->getOptionByCode('simple_product');
        if ($simpleOption !== null && $simpleOption->getProduct() !== null) {
            return (int)$simpleOption->getProduct()->getId();
        }

        return $productId;
    }

    /**
     * Leaf id for an order line.
     *
     * Child order-item row ONLY. Magento writes one for every configurable
     * sale and it carries the child's entity id directly, so this stays a pure
     * read off the already loaded order.
     *
     * Deliberately does NOT fall back to product_options['simple_sku']: that
     * option holds a SKU, not an id, so using it would mean a catalog lookup
     * per line — an N+1 on the purchase event, the hottest one the pixel emits.
     *
     * Returns null when no child row exists (non-configurable lines, or
     * partial/imported order data); the caller then keeps the parent id.
     *
     * @param  OrderItemInterface $item
     * @return int|null
     */
    public function forOrderItem(OrderItemInterface $item)
    {
        if ($item->getProductType() !== 'configurable') {
            return null;
        }

        try {
            $children = $item->getChildrenItems();
        } catch (\Throwable $e) {
            // Partial order item — caller keeps the parent id.
            return null;
        }

        if (!is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            $childId = (int)$child->getProductId();
            if ($childId > 0) {
                return $childId;
            }
        }

        return null;
    }
}
