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
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory
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
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    private $_chimpErrors;

    /**
     * Result constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\Collection $batchCollection
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $chimpErrors
     * @param \Magento\Framework\Archive $archive
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory $batchCollection,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $chimpErrors,
        \Magento\Framework\Archive $archive
    ) {
    
        $this->_batchCollection     = $batchCollection;
        $this->_helper              = $helper;
        $this->_archive             = $archive;
        $this->_chimpErrors         = $chimpErrors;
    }
    public function processResponses($storeId, $isMailChimpStoreId = false, $mailchimpStoreId)
    {
        $collection = $this->_batchCollection->create();
        $collection
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('status', ['eq' => 'pending'])
            ->addFieldToFilter('mailchimp_store_id', ['eq' => $mailchimpStoreId]);
        /**
         * @var $item \Ebizmarts\MailChimp\Model\MailChimpSyncBatches
         */
        $item = null;
        foreach ($collection as $item) {
            try {
                $files = $this->getBatchResponse($item->getBatchId(), $storeId);
                if (is_array($files) && count($files)) {
                    $this->processEachResponseFile($files, $item->getBatchId(), $mailchimpStoreId, $storeId);
                    $item->setStatus('completed');
                    $item->setModifiedDate( $this->_helper->getGmtDate() );
                    $item->getResource()->save($item);
                } elseif ($files === false) {
                    $item->setStatus('canceled');
                    $item->getResource()->save($item);
                    continue;
                }
                $baseDir = $this->_helper->getBaseDir();
                if (is_dir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId())) {
                    array_map('unlink', glob($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId().DIRECTORY_SEPARATOR."*.*"));
                    rmdir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId());
                }
            } catch (\Exception $e) {
                $this->_helper->log("Error with a response: " . $e->getMessage());
            }
        }
    }
    public function getBatchResponse($batchId, $storeId = null)
    {
        $files = [];
        try {
            $baseDir = $this->_helper->getBaseDir();
            $api = $this->_helper->getApi($storeId);
            // check the status of the job
            $response = $api->batchOperation->status($batchId);

            if (isset($response['status']) && $response['status'] == 'finished') {
                // Create temporary directory, if that does not exist
                if (!is_dir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR)) {
                    mkdir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR);
                }
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
            $this->_helper->log($e->getFriendlyMessage());
            return false;
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $files;
    }
    protected function processEachResponseFile($files, $batchId, $mailchimpStoreId, $storeId)
    {
        foreach ($files as $file) {
            $items = json_decode(file_get_contents($file));
            if ($items!==false) {
                foreach ($items as $item) {
                    $line = explode('_', $item->operation_id);
                    $type = $line[0];
                    $id = $line[2];
                    if ($item->status_code != 200) {
                        //parse error
                        $response = json_decode($item->response);
                        if (preg_match('/already exists/', $response->detail)) {
                            $chimpSync = $this->_helper->getChimpSyncEcommerce($mailchimpStoreId, $id, $type);
                            $chimpSync->setData('mailchimp_sent', \Ebizmarts\MailChimp\Helper\Data::SYNCED);
                            $chimpSync->getResource()->save($chimpSync);
                            continue;
                        }
                        $mailchimpErrors = $this->_chimpErrors->create();
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
                        /**
                         * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSync
                         */
                        $chimpSync = $this->_helper->getChimpSyncEcommerce($mailchimpStoreId, $id, $type);
                        $chimpSync->setData("mailchimp_sync_error", $error);
                        $chimpSync->setData('mailchimp_sent', \Ebizmarts\MailChimp\Helper\Data::SYNCERROR);
                        $chimpSync->getResource()->save($chimpSync);
                        $mailchimpErrors->setType($response->type);
                        $mailchimpErrors->setTitle($response->title);
                        $mailchimpErrors->setStatus($item->status_code);
                        $mailchimpErrors->setErrors($errorDetails);
                        $mailchimpErrors->setRegtype($type);
                        $mailchimpErrors->setOriginalId($id);
                        $mailchimpErrors->setBatchId($batchId);
                        $mailchimpErrors->setMailchimpStoreId($mailchimpStoreId);
                        $mailchimpErrors->setOriginalId($id);
                        $mailchimpErrors->setBatchId($batchId);
                        $mailchimpErrors->setStoreId($storeId);
                        $mailchimpErrors->getResource()->save($mailchimpErrors);
                    } else {
                        $chimpSync = $this->_helper->getChimpSyncEcommerce($mailchimpStoreId, $id, $type);
                        $chimpSync->setData('mailchimp_sent', \Ebizmarts\MailChimp\Helper\Data::SYNCED);
                        $chimpSync->getResource()->save($chimpSync);
                    }
                }
            } else {
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $this->_helper->log(' - Maximum stack depth exceeded');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $this->_helper->log(' - Unexpected control character found');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $this->_helper->log(' - Syntax error, malformed JSON');
                        break;
                    case JSON_ERROR_NONE:
                        $this->_helper->log(' - No errors');
                        break;
                }
            }
            unlink($file);
        }
    }
}
