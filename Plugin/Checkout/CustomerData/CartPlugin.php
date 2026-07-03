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

namespace Ebizmarts\MailChimp\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Adds cart_id and currency_code to the cart customer-data section so the
 * Mailchimp Pixel JS can include them in PRODUCT_ADDED_TO_CART payloads.
 */
class CartPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Cart  $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $result['cart_id']      = $quote ? (string)$quote->getId() : '';
            $result['currency_code'] = $quote ? (string)$quote->getQuoteCurrencyCode() : '';
        } catch (\Exception $e) {
            $result['cart_id']      = '';
            $result['currency_code'] = '';
        }

        return $result;
    }
}
