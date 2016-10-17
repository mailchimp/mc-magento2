<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/1/16 10:02 AM
 * @file: Ecommerce.php
 */
namespace Ebizmarts\MailChimp\Cron;

class Ecommerce
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $_storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Product
     */
    private $_apiProduct;

    /**
     * Ecommerce constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Reports\Model\ResourceModel\Quote\Collection $quoteCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Product $apiProduct
    )
    {
        $this->_storeManager    = $storeManager;
        $this->_helper          = $helper;
        $this->_apiProduct      = $apiProduct;
    }

    public function execute()
    {
        $this->_helper->log(__METHOD__);
        foreach($this->_storeManager->getStores() as $storeId => $val)
        {
            $this->_storeManager->setCurrentStore($storeId);
            if($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE)) {
                $this->_processStore($storeId);
            }
        }
    }
    protected function _processStore()
    {
        $batchArray = array();
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE)) {
            $batchArray['operations'] =  array_merge($batchArray['operations'], $this->_apiProduct->_sendProducts());
        }


        if (!empty($batchArray['operations'])) {
            try {
                $batchJson = json_encode($batchArray);
                if (!$batchJson || $batchJson == '') {
                    $this->_helper->log('An empty operation was detected');
                } else {
                    $api = $this->_helper->getApi();
                    $batchResponse =$api->batchOperation->add($batchJson);
                }

            } catch(Exception $e) {
                $this->_helper->log("Json encode fails");
                $this->_helper->log(var_export($batchArray,true));
            }
        }


    }

}