<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 1/18/18 12:30 PM
 * @file: SaveAfter.php
 */
namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurable;

    /**
     * SaveAfter constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable
    ) {

        $this->helper               = $helper;
        $this->configurable         = $configurable;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Catalog\Model\Product $product
         */
        $product = $observer->getProduct();
        $mailchimpStore = $this->helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $product->getStoreId()
        );
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $parents = $this->configurable->getParentIdsByChild($product->getId());
            if (is_array($parents)) {
                foreach ($parents as $parentid) {
                    $this->_updateProduct($parentid);
                }
            } elseif ($parents) {
                $this->_updateProduct($parents);
            }
        }
        $this->_updateProduct($product->getId());
    }
    protected function _updateProduct($entityId)
    {
        $this->helper->markRegisterAsModified($entityId, \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT);
    }
}
