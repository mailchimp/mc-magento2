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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;

/**
 * Exposes current cart data as JSON for the Mailchimp Pixel JS module.
 * Rendered on checkout_cart_index pages (cart page).
 */
class Cart extends Template
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
     * @param Template\Context $context
     * @param MailChimpHelper  $helper
     * @param CheckoutSession  $checkoutSession
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper          = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the cart data array for CART_VIEWED payload.
     *
     * @return array
     */
    public function getCartData(): array
    {
        $quote    = $this->checkoutSession->getQuote();
        $currency = $this->_storeManager->getStore()->getCurrentCurrencyCode();

        $mediaBaseUrl = $this->_urlBuilder->getBaseUrl(
            ['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]
        );

        $lineItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productId = (string)$item->getProductId();
            $product   = $item->getProduct();
            $imageUrl  = '';
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
                    'id'         => $productId,
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

        return [
            'id'         => (string)$quote->getId(),
            'lineItems'  => $lineItems,
            'totalPrice' => (float)$quote->getGrandTotal(),
            'currency'   => $currency,
        ];
    }

    /**
     * Only render when module + pixel are enabled and cart has items.
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
