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
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Exposes current category data as JSON for the Mailchimp Pixel JS module.
 * Rendered on catalog_category_view pages.
 */
class Category extends Template
{
    /**
     * @var MailChimpHelper
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Template\Context $context
     * @param MailChimpHelper  $helper
     * @param Registry         $registry
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        MailChimpHelper $helper,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper   = $helper;
        $this->registry = $registry;
    }

    /**
     * Returns the current category data for CATEGORY_VIEWED payload.
     *
     * @return array
     */
    public function getCategoryData(): array
    {
        $category = $this->registry->registry('current_category');
        if (!$category) {
            return [];
        }

        return [
            'categoryId'   => (string)$category->getId(),
            'categoryName' => (string)$category->getName(),
        ];
    }

    /**
     * Only render when pixel is enabled and a category is available.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();
        if (!$this->helper->isPixelEnabled($storeId) || !$this->registry->registry('current_category')) {
            return '';
        }
        return parent::_toHtml();
    }
}
