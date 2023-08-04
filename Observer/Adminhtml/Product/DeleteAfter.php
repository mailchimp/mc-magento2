<?php

namespace Ebizmarts\MailChimp\Observer\Adminhtml\Product;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteAfter implements ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param Configurable $configurable
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        Configurable $configurable

    ) {
        $this->helper = $helper;
        $this->syncHelper = $syncHelper;
        $this->configurable = $configurable;
    }

    function execute(Observer $observer)
    {
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
        $this->syncHelper->markEcommerceAsDeleted($entityId, \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT);
    }
}
