<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/21/16 4:09 PM
 * @file: Result.php
 */

namespace Ebizmarts\MailChimp\Model\Api;

class Result
{
    const MAILCHIMP_TEMP_DIR = 'Mailchimp';
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\Collection
     */
    private $_batchCollection;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Magento\Framework\Archive
     */
    private $_archive;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrors
     */
    private $_chimpErrors;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $_productRepository;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $_orderRepository;

    private $_chimpSyncEcommerce;


    /**
     * Result constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\Collection $batchCollection
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
     * @param \Magento\Framework\Archive $archive
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\Collection $batchCollection,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors,
        \Magento\Framework\Archive $archive,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
    )
    {
        $this->_batchCollection     = $batchCollection;
        $this->_helper              = $helper;
        $this->_archive             = $archive;
        $this->_chimpErrors         = $chimpErrors;
        $this->_productRepository   = $productRepository;
        $this->_customerRepository  = $customerRepository;
        $this->_orderRepository     = $orderRepository;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
    }
    public function processResponses($storeId, $isMailChimpStoreId = false)
    {
        $collection = $this->_batchCollection;
        $collection
            ->addFieldToFilter('store_id', array('eq' => $storeId))
            ->addFieldToFilter('status', array('eq' => 'pending'));
        /**
         * @var $item \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
         */
        $item = null;
        foreach ($collection as $item) {
            try {
                $storeId = ($isMailChimpStoreId) ? 0 : $storeId;
                $files = $this->getBatchResponse($item->getBatchId(), $storeId);
                if (count($files)) {
                    $this->processEachResponseFile($files, $item->getBatchId());
                    $item->setStatus('completed');
                    $item->getResource()->save($item);
                }
                $baseDir = $this->_helper->getBaseDir();
                if (is_dir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId())) {
                    rmdir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId());
                }
            } catch (\Exception $e) {
                $this->_helper->log("Error with a response: " . $e->getMessage());
            }
        }
    }
    protected function getBatchResponse($batchId, $storeId = 0)
    {
        $files = array();
        try {
            $baseDir = $this->_helper->getBaseDir();
            $api = $this->_helper->getApi();
            // check the status of the job
            $response = $api->batchOperation->status($batchId);
            if (isset($response['status']) && $response['status'] == 'finished') {
                // get the tar.gz file with the results
                $fileUrl = urldecode($response['response_body_url']);
                $fileName = $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId;
                $fd = fopen($fileName . '.tar.gz', 'w');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fileUrl);
                curl_setopt($ch, CURLOPT_FILE, $fd);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this will follow redirects
                $r = curl_exec($ch);
                curl_close($ch);
                fclose($fd);
                mkdir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
                $archive = $this->_archive;
                $archive->unpack($fileName . '.tar.gz', $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
                $archive->unpack($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $batchId . '.tar', $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
                $dir = scandir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
                foreach ($dir as $d) {
                    $name = pathinfo($d);
                    if ($name['extension'] == 'json') {
                        $files[] = $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $d;
                    }
                }
                unlink($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $batchId . '.tar');
                unlink($fileName . '.tar.gz');
            }
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getMessage());
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $files;
    }
    protected function processEachResponseFile($files, $batchId)
    {
        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));
            foreach ($items as $item) {
                if ($item->status_code != 200) {
                    $line = explode('_', $item->operation_id);
                    $type = $line[0];
                    $id = $line[2];

                    $mailchimpErrors = $this->_chimpErrors;

                    //parse error
                    $response = json_decode($item->response);
                    $errorDetails = "";
                    if (!empty($response->errors)) {
                        foreach ($response->errors as $error) {
                            if (isset($error->field) && isset($error->message)) {
                                $errorDetails .= $errorDetails != "" ? " / " : "";
                                $errorDetails .= $error->field . " : " . $error->message;
                            }
                        }
                    }
                    if ($errorDetails == "") {
                        $errorDetails = $response->detail;
                    }

                    $error = $response->title . " : " . $response->detail;

                    switch ($type) {
                        case \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT:
                            $p = $this->_productRepository->getById($id);
                            if ($p->getId() == $id) {
                                $p->setData("mailchimp_sync_error", $error);
                                //$p->setMailchimpUpdateObserverRan(true);
                                $this->_productRepository->save($p);
                            } else {
                                $this->_helper->log("Error: product " . $id . " not found");
                            }
                            break;
                        case \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER:
                            $c = $this->_customerRepository->getById($id);
                            if ($c->getId() == $id) {
//                                $c->setCustomAttribute("mailchimp_sync_error", $error);
                                $this->_customerRepository->save($c);
                            } else {
                                $this->_helper->log("Error: customer " . $id . " not found");
                            }
                            break;
                        case \Ebizmarts\MailChimp\Helper\Data::IS_ORDER:
                            $o = $this->_orderRepository->get($id);
                            if ($o->getId() == $id) {
                                $c = $this->_chimpSyncEcommerce->getByStoreIdType($o->getStoreId(),$id,$type);
                                $c->setData("mailchimp_sync_error", $error);
                                $c->getResource()->save($c);
                            } else {
                                $this->_helper->log("Error: order " . $id . " not found");
                            }
                            break;
//                        case \Ebizmarts\MailChimp\Helper\Data::IS_QUOTE:
//                            $q = Mage::getModel('sales/quote')->load($id);
//                            if ($q->getId() == $id) {
//                                $q->setData("mailchimp_sync_error", $error);
//                                $q->save();
//                            } else {
//                                $this->_helper->log("Error: quote " . $id . " not found");
//                            }
//                            break;
//                        case \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER:
//                            $s = Mage::getModel('newsletter/subscriber')->load($id);
//                            if ($s->getId() == $id) {
//                                $s->setData("mailchimp_sync_error", $error);
//                                $s->save();
//                            } else {
//                                $this->_helper->log("Error: subscriber " . $id . " not found");
//                            }
//                            break;
                        default:
                            $this->_helper->log("Error: no identification " . $type . " found");
                            break;
                    }
                    $mailchimpErrors->setType($response->type);
                    $mailchimpErrors->setTitle($response->title);
                    $mailchimpErrors->setStatus($item->status_code);
                    $mailchimpErrors->setErrors($errorDetails);
                    $mailchimpErrors->setRegtype($type);
                    $mailchimpErrors->setOriginalId($id);
                    $mailchimpErrors->setBatchId($batchId);
                    $mailchimpErrors->getResource()->save($mailchimpErrors);
                }
            }
            unlink($file);
        }
    }
}