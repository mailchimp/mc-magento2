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
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;

/**
 * Resolves the logged-in customer email server-side and emits a minimal
 * JSON island for the Pixel JS to call identify().
 *
 * WHY server-side (not purely client-side):
 *   Magento's customer-data section is available client-side but requires an
 *   async XHR on cache-miss. Resolving the email here avoids that race
 *   condition: the island is present in the initial HTML, so identify() fires
 *   before any deferred section loads.
 *
 *   Only the email is exposed — no other PII. The JS hashes it before
 *   sending to the Pixel SDK (SHA-256 when SubtleCrypto is available).
 *
 * For guest customers identify() is triggered from the checkout email field
 * blur event in pixel.js (hookCheckoutEmailField), so no server-side data
 * is needed for that path.
 */
class Identity extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param Template\Context $context
     * @param MailChimpHelper  $helper
     * @param CustomerSession  $customerSession
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper          = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * Returns the identity data for the logged-in customer, or null for guests.
     *
     * @return array|null
     */
    public function getIdentityData(): ?array
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }

        $email = (string)$this->customerSession->getCustomer()->getEmail();
        if (!$email) {
            return null;
        }

        return ['email' => $email];
    }

    /**
     * Only render when pixel is enabled and customer is logged in.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId)) {
            return '';
        }
        if ($this->getIdentityData() === null) {
            return '';
        }
        return parent::_toHtml();
    }
}
