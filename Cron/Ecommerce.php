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
     * @var \Ebizmarts\MailChimp\Model\Api\Result
     */
    private $_apiResult;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
     */
    private $_mailChimpSyncBatches;

    /**
     * Ecommerce constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Api\Product $apiProduct
     * @param \Ebizmarts\MailChimp\Model\Api\Result $apiResult
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Product $apiProduct,
        \Ebizmarts\MailChimp\Model\Api\Result $apiResult,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncBatches
    )
    {
        $this->_storeManager    = $storeManager;
        $this->_helper          = $helper;
        $this->_apiProduct      = $apiProduct;
        $this->_mailChimpSyncBatches    = $mailChimpSyncBatches;
        $this->_apiResult       = $apiResult;
    }

    public function execute()
    {
        foreach($this->_storeManager->getStores() as $storeId => $val)
        {
            $this->_storeManager->setCurrentStore($storeId);
            if($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE)) {
                $this->_apiResult->processResponses($storeId,true);
                $this->_processStore($storeId);
            }
        }
    }

    protected function _processStore($storeId)
    {
        $batchArray = array();
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ECOMMERCE_ACTIVE)) {
            $batchArray['operations'] =  $this->_apiProduct->_sendProducts($storeId);
        }

        if (!empty($batchArray['operations'])) {
            try {
                $batchJson = json_encode($batchArray);
                $this->_helper->log($batchJson);

                if (!$batchJson || $batchJson == '') {
                    $this->_helper->log('An empty operation was detected');
                } else {
                    $api = $this->_helper->getApi();
                    $batchResponse =$api->batchOperation->add($batchArray);
//                    $this->_helper->log(var_export($batchResponse,true));
                    $this->_mailChimpSyncBatches->setStoreId($storeId);
                    $this->_mailChimpSyncBatches->setBatchId($batchResponse['id']);
                    $this->_mailChimpSyncBatches->setStatus($batchResponse['status']);
                    $this->_mailChimpSyncBatches->getResource()->save($this->_mailChimpSyncBatches);
                }
            } catch(Exception $e) {
                $this->_helper->log("Json encode fails");
                $this->_helper->log(var_export($batchArray,true));
            }
        }
    }

}