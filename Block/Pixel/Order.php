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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;

/**
 * Exposes completed order data as JSON for the Mailchimp Pixel JS module.
 * Rendered on checkout_onepage_success pages.
 */
class Order extends Template
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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LeafProductIdResolver
     */
    protected $leafProductIdResolver;

    /**
     * @param Template\Context           $context
     * @param MailChimpHelper            $helper
     * @param CheckoutSession            $checkoutSession
     * @param ProductRepositoryInterface $productRepository
     * @param LeafProductIdResolver      $leafProductIdResolver
     * @param array                      $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        CheckoutSession $checkoutSession,
        ProductRepositoryInterface $productRepository,
        LeafProductIdResolver $leafProductIdResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper                = $helper;
        $this->checkoutSession       = $checkoutSession;
        $this->productRepository     = $productRepository;
        $this->leafProductIdResolver = $leafProductIdResolver;
    }

    /**
     * Returns the order data array for PURCHASED payload.
     *
     * @return array
     */
    public function getOrderData(): array
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            return [];
        }

        $currency     = $order->getOrderCurrencyCode();
        $mediaBaseUrl = $this->_urlBuilder->getBaseUrl(
            ['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]
        );

        $lineItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $productId  = (string)$item->getProductId();
            // Mailchimp variant identity: the child order-item row for a
            // configurable, the parent id for everything else.
            $leafId     = $this->leafProductIdResolver->forOrderItem($item);
            $variantId  = $leafId !== null ? (string)$leafId : $productId;
            $imageUrl   = '';
            $productUrl = '';
            try {
                $product = $this->productRepository->getById((int)$item->getProductId());
                $img     = $product->getImage();
                if ($img && $img !== 'no_selection') {
                    $imageUrl = $mediaBaseUrl . 'catalog/product' . $img;
                }
                $productUrl = (string)$product->getProductUrl();
            } catch (\Exception $e) {
                // skip — product may have been deleted
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
                'quantity' => (int)$item->getQtyOrdered(),
                'price'    => (float)$item->getRowTotal(),
            ];
        }

        $payload = [
            'id'            => (string)$order->getIncrementId(),
            'lineItems'     => $lineItems,
            'subtotalPrice' => (float)$order->getSubtotal(),
            'totalTax'      => (float)$order->getTaxAmount(),
            'totalShipping' => (float)$order->getShippingAmount(),
            'totalPrice'    => (float)$order->getGrandTotal(),
            'currency'      => $currency,
        ];

        if (!$order->getCustomerIsGuest() && $order->getCustomerId()) {
            $payload['customerId'] = (string)$order->getCustomerId();
        }

        return $payload;
    }

    /**
     * Only render when module + pixel are enabled and order exists.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId)) {
            return '';
        }
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            return '';
        }
        return parent::_toHtml();
    }
}
