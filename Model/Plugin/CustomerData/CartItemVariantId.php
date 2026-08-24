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

namespace Ebizmarts\MailChimp\Model\Plugin\CustomerData;

use Ebizmarts\MailChimp\Model\Product\LeafProductIdResolver;
use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Quote\Model\Quote\Item;

/**
 * Exposes the leaf (variant) product id in the `cart` customer-data section.
 *
 * The PRODUCT_ADDED_TO_CART pixel event is fired client side from
 * Magento_Customer/js/customer-data, and that section only carries
 * `product_id`, which for a configurable is the PARENT. There is no way to
 * resolve the chosen child in the browser, so the value is attached here and
 * read back in view/frontend/web/js/pixel.js.
 *
 * Every cart item is rendered by DefaultItem — Magento ships no configurable
 * specific renderer for this section — so this single plugin covers all types.
 */
class CartItemVariantId
{
    /**
     * @var LeafProductIdResolver
     */
    protected $leafProductIdResolver;

    /**
     * @param LeafProductIdResolver $leafProductIdResolver
     */
    public function __construct(
        LeafProductIdResolver $leafProductIdResolver
    ) {
        $this->leafProductIdResolver = $leafProductIdResolver;
    }

    /**
     * Attach the leaf (variant) product id to the cart section item data.
     *
     * @param  DefaultItem $subject
     * @param  array       $result
     * @param  Item        $item
     * @return array
     */
    public function afterGetItemData(DefaultItem $subject, $result, Item $item)
    {
        if (!is_array($result)) {
            return $result;
        }

        $result['mc_variant_id'] = (string)$this->leafProductIdResolver->forQuoteItem($item);

        return $result;
    }
}
