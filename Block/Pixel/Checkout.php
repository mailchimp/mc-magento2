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
use Ebizmarts\MailChimp\Model\Product\LeafProductIdResolver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;

/**
 * Exposes checkout data as JSON for the Mailchimp Pixel JS module.
 * Rendered on checkout_index_index pages (checkout page).
 */
class Checkout extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var LeafProductIdResolver
     */
    protected $leafProductIdResolver;

    /**
     * @param Template\Context      $context
     * @param MailChimpHelper       $helper
     * @param CheckoutSession       $checkoutSession
     * @param LeafProductIdResolver $leafProductIdResolver
     * @param array                 $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        CheckoutSession $checkoutSession,
        LeafProductIdResolver $leafProductIdResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper                = $helper;
        $this->checkoutSession       = $checkoutSession;
        $this->leafProductIdResolver = $leafProductIdResolver;
    }

    /**
     * Returns the checkout data array for CHECKOUT_STARTED payload.
     *
     * @return array
     */
    public function getCheckoutData(): array
    {
        $quote    = $this->checkoutSession->getQuote();
        $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

        $mediaBaseUrl = $this->_urlBuilder->getBaseUrl(
            ['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]
        );

        $lineItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productId  = (string)$item->getProductId();
            // Mailchimp variant identity: the chosen child for a configurable, the
            // product itself otherwise. Must match what the REST sync publishes.
            $variantId  = (string)$this->leafProductIdResolver->forQuoteItem($item);
            $product    = $item->getProduct();
            $imageUrl   = '';
            $productUrl = '';
            if ($product) {
                $img = $product->getImage();
                if ($img && $img !== 'no_selection') {
                    $imageUrl = $mediaBaseUrl . 'catalog/product' . $img;
                }
                $productUrl = (string)$product->getProductUrl();
            }
            $lineItems[] = [
                'item'     => [
                    'id'         => $variantId,
                    'productId'  => $productId,
                    'title'      => (string)$item->getName(),
                    'price'      => (float)$item->getPrice(),
                    'currency'   => $currency,
                    'sku'        => (string)$item->getSku(),
                    'imageUrl'   => $imageUrl,
                    'productUrl' => $productUrl,
                    'vendor'     => '',
                    'categories' => [],
                ],
                'quantity' => (int)$item->getQty(),
                'price'    => (float)($item->getPrice() * $item->getQty()),
            ];
        }

        $discounts  = [];
        $couponCode = $quote->getCouponCode();
        if ($couponCode) {
            $discounts[] = [
                'code'   => $couponCode,
                'amount' => (float)abs($quote->getShippingAddress()->getDiscountAmount()),
                'type'   => 'fixed',
            ];
        }

        return [
            'id'            => 'checkout_' . $quote->getId(),
            'cartId'        => (string)$quote->getId(),
            'lineItems'     => $lineItems,
            'subtotalPrice' => (float)$quote->getSubtotal(),
            'totalTax'      => (float)$quote->getShippingAddress()->getTaxAmount(),
            'totalShipping' => (float)$quote->getShippingAddress()->getShippingAmount(),
            'totalPrice'    => (float)$quote->getGrandTotal(),
            'currency'      => $currency,
            'discounts'     => $discounts,
        ];
    }

    /**
     * Only render when module + pixel are enabled.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId)) {
            return '';
        }
        return parent::_toHtml();
    }
}
