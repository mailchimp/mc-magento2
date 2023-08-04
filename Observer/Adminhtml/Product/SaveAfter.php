<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

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
     * @var SyncHelper
     */
    private $syncHelper;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable
     * @param SyncHelper $syncHelper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable,
        SyncHelper $syncHelper
    ) {
        $this->helper = $helper;
        $this->configurable = $configurable;
        $this->syncHelper = $syncHelper;
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
        $sync = $product->getSync();
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $parents = $this->configurable->getParentIdsByChild($product->getId());
            if (is_array($parents)) {
                foreach ($parents as $parentid) {
                    $this->_updateProduct($parentid, $sync);
                }
            } elseif ($parents) {
                $this->_updateProduct($parents, $sync);
            }
        }
        $this->_updateProduct($product->getId(), $sync);
    }

    protected function _updateProduct($entityId, $sync)
    {
        if (!$sync) {
            $this->syncHelper->markRegisterAsModified($entityId, \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT);
        }
    }
}
