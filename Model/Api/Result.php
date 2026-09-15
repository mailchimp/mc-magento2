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

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Ebizmarts\Mailchimp\Helper\Data as Helper;

class Result
{
    const MAILCHIMP_TEMP_DIR = 'Mailchimp';

    /**
     * How many times a FINISHED batch may fail to give up its result before we
     * stop asking for it.
     *
     * Only readings of a finished batch are counted. Polling a batch that has
     * not finished is not a retry -- it is the normal case, it is what the
     * endpoint is for, and it ends on its own: a batch that never finishes is
     * eventually expired by Mailchimp, and the status call then fails in a way
     * that is already handled.
     *
     * Five, at the five-minute cadence this extension ships with, is about
     * twenty-five minutes of a condition already known to be wrong, since the
     * work itself completed on the other side. Long enough for a transient
     * network or storage fault, and short enough that it does not re-download
     * a result that never parses, every five minutes, until Mailchimp expires
     * the batch a week later.
     */
    const MAX_RESPONSE_ATTEMPTS = 5;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory
     */
    private $_batchCollection;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var \Magento\Framework\Archive
     */
    private $_archive;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    private $_chimpErrors;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $_driver;
    /**
     * @var \Magento\Framework\HTTP\Client\CurlFactory
     */
    private $_curlFactory;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory $batchCollection
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $chimpErrors
     * @param \Magento\Framework\Archive $archive
     * @param \Magento\Framework\Filesystem\Driver\File $driver
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncBatches\CollectionFactory $batchCollection,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $chimpErrors,
        \Magento\Framework\Archive $archive,
        \Magento\Framework\Filesystem\Driver\File $driver,
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
    ) {

        $this->_batchCollection     = $batchCollection;
        $this->_helper              = $helper;
        $this->syncHelper           = $syncHelper;
        $this->_archive             = $archive;
        $this->_chimpErrors         = $chimpErrors;
        $this->_driver              = $driver;
        $this->_curlFactory         = $curlFactory;
    }
    public function processResponses($storeId, $isMailChimpStoreId = false, $mailchimpStoreId=null)
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
                    $item->setStatus(\Ebizmarts\MailChimp\Helper\Data::BATCH_COMPLETED);
                    $item->setModifiedDate($this->_helper->getGmtDate());
                    $item->getResource()->save($item);
                } elseif ($files === false) {
                    $item->setStatus(\Ebizmarts\MailChimp\Helper\Data::BATCH_ERROR);
                    $item->getResource()->save($item);
                    $this->syncHelper->deleteAllByBatchId($item->getBatchId());
                    continue;
                } elseif (is_array($files)) {
                    // Finished on Mailchimp, and we could not read what it
                    // said. Not the same as `null` above, which is a batch
                    // still being processed -- that one is waited for, not
                    // counted, because waiting is the only correct thing to do
                    // and it ends on its own.
                    if ($this->giveUpOnResponse($item)) {
                        continue;
                    }
                }
                $baseDir = $this->_helper->getBaseDir();
                if ($this->_driver->isDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                    self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId())) {
                    $dirFiles = $this->_driver->readDirectory(
                        $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                        self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR .
                        $item->getBatchId().DIRECTORY_SEPARATOR
                    );
                    foreach ($dirFiles as $dirFile) {
                        $this->_driver->deleteFile(
                            $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                            self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR .
                            $item->getBatchId().DIRECTORY_SEPARATOR.$dirFile
                        );
                    }
                    $this->_driver->deleteDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                        self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $item->getBatchId());
                }
            } catch (\Exception $e) {
                $this->_helper->log("Error with a response: " . $e->getMessage());
            }
        }
    }
    /**
     * Count one failed reading of a finished batch, and decide whether to stop.
     *
     * The operations already ran on Mailchimp, so what is lost by giving up is
     * the record of which of them failed -- and that is exactly what cannot be
     * recovered by asking again once the result is unreadable. Asking forever
     * does not recover it either: it re-downloads the same result every run
     * until Mailchimp expires the batch, which is the state this exists to end.
     *
     * Giving up re-sends everything the batch carried rather than deleting the
     * sync rows. The entities are found by the batch id they still carry, and
     * marked so the next run picks them up again -- the same three fields the
     * rest of the extension uses to say "send this again", because one of them
     * is not enough on its own.
     *
     * @param  \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $item
     * @return bool  whether this batch was given up on
     */
    private function giveUpOnResponse($item)
    {
        $attempts = (int)$item->getResponseAttempts() + 1;
        $item->setResponseAttempts($attempts);

        if ($attempts < self::MAX_RESPONSE_ATTEMPTS) {
            $item->getResource()->save($item);

            return false;
        }

        $item->setStatus(\Ebizmarts\MailChimp\Helper\Data::BATCH_ERROR);
        $item->getResource()->save($item);
        $this->syncHelper->markAllAsModifiedByBatchId($item->getBatchId());
        $this->_helper->log(
            "Giving up on the result of batch [" . $item->getBatchId() . "] after " . $attempts
            . " attempts; everything it carried has been queued to be sent again"
        );

        return true;
    }

    /**
     * The result files of a batch, or what went wrong getting them.
     *
     * Three outcomes, and the caller has to be able to tell them apart:
     *
     *   array  the result was read -- empty if the archive carried no files
     *   null   the batch has not finished; there is nothing to read yet
     *   false  the status call itself failed
     *
     * `null` is new. It used to return the same empty array as a failed read,
     * so a batch still being processed and a batch whose result could not be
     * fetched were indistinguishable from the outside -- which is why nobody
     * could see the second one, and why a count of attempts would have counted
     * the normal case.
     *
     * @param  string $batchId
     * @param  int|null $storeId
     * @return array|null|false
     */
    public function getBatchResponse($batchId, $storeId = null)
    {
        $files = [];
        $baseDir = $this->_helper->getBaseDir();
        $fileName = $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
            self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId;
        try {
            $api = $this->_helper->getApi($storeId);
            // check the status of the job
            $response = $api->batchOperation->status($batchId);

            if (!isset($response['status']) || $response['status'] != 'finished') {
                return null;
            }

            // Create temporary directory, if that does not exist
            if (!$this->_driver->isDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR)) {
                $this->_driver->createDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR);
            }
            // get the tar.gz file with the results
            // for AWS S3 use urldecode, for google drive use without urldecode
            // $fileUrl = urldecode($response['response_body_url']);
            $fileUrl = $response['response_body_url'];
            $fd = $this->_driver->fileOpen($fileName . '.tar.gz', 'w');
            $ch = $this->_curlFactory->create();
            $ch->setOption(CURLOPT_URL, $fileUrl);
            $ch->setOption(CURLOPT_FILE, $fd);
            $ch->setOption(CURLOPT_FOLLOWLOCATION, true);
            $r =$ch->get($fileUrl);
            $this->_driver->fileClose($fd);

            $this->_driver->createDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
            $archive = $this->_archive;
            $archive->unpack(
                $fileName . '.tar.gz',
                $baseDir . DIRECTORY_SEPARATOR . 'var' .
                DIRECTORY_SEPARATOR . self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId
            );
            $archive->unpack(
                $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $batchId . '.tar',
                $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId
            );
            $dirFiles = $this->_driver->readDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
            foreach ($dirFiles as $dirFile) {
                $name = pathinfo($dirFile);
                if ($name['extension'] == 'json') {
                    $files[] = $dirFile;
                }
            }
            $this->_driver->deleteFile($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $batchId . '.tar');
            $this->_driver->deleteFile($fileName . '.tar.gz');
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->_helper->log($e->getFriendlyMessage());
            return false;
        } catch (\Exception $e) {
            // The message is what says which of the many ways this can fail
            // actually happened -- the archive could not be written, the
            // filesystem is full, the download returned something that is not
            // an archive. Logging a fixed string instead sent whoever read it
            // looking at the wrong thing, since the only other lines that land
            // here come from the cleanup below and name a missing directory.
            $this->_helper->log(
                "Something went wrong retrieving result for batch [$batchId]: " . $e->getMessage()
            );
            $this->_helper->log("Deleting temporary files, will retry the next run don't worry");

            try {
                $this->_driver->deleteFile($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                    self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId . '/' . $batchId . '.tar');
                $this->_driver->deleteFile($fileName . '.tar.gz');
                $this->_driver->deleteDirectory($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .
                    self::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
            } catch(\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return $files;
    }
    protected function processEachResponseFile($files, $batchId, $mailchimpStoreId, $storeId)
    {
        $listId = $this->_helper->getDefaultList($storeId);
        foreach ($files as $file) {
            $items = json_decode($this->_driver->fileGetContents($file));
            if ($items!==false) {
                foreach ($items as $item) {
                    $line = explode('_', $item->operation_id);
                    $type = $line[0];
                    $id = $line[2];
                    if ($item->status_code != 200) {
                        //parse error
                        $response = json_decode($item->response);
                        if (preg_match('/already exists/', $response->detail)) {
                            $this->_updateSyncData(
                                $mailchimpStoreId,
                                $listId,
                                $type,
                                $id,
                                null,
                                \Ebizmarts\MailChimp\Helper\Data::SYNCED
                            );
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
                        if ($type == Helper::IS_PRODUCT and $item->status_code == 404) {
                            $this->syncHelper->deleteByTypeAndId($type, $id, $mailchimpStoreId);
                        } else {
                            $this->_updateSyncData(
                                $mailchimpStoreId,
                                $listId,
                                $type,
                                $id,
                                $error,
                                \Ebizmarts\MailChimp\Helper\Data::SYNCERROR
                            );
                        }
                        if (property_exists($response, 'type')){
                            $mailchimpErrors->setType($response->type);
                        } else {
                            $mailchimpErrors->setType('Unknown');
                        }
                        if (property_exists($response, 'title')){
                            $mailchimpErrors->setTitle($response->title);
                        } else {
                            $mailchimpErrors->setTitle('Unknown');
                        }
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
                        $this->_updateSyncData(
                            $mailchimpStoreId,
                            $listId,
                            $type,
                            $id,
                            null,
                            \Ebizmarts\MailChimp\Helper\Data::SYNCED
                        );
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
            $this->_driver->deleteFile($file);
        }
    }
    private function _updateSyncData($mailchimpStoreId, $listId, $type, $id, $error, $status)
    {
        /**
         * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSync
         */
        if ($type == \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER) {
            $mailchimpStore = $listId;
        } else {
            $mailchimpStore = $mailchimpStoreId;
        }
        $this->syncHelper->saveEcommerceData(
            $mailchimpStore,
            $id,
            $type,
            null,
            $error,
            null,
            null,
            null,
            $status,
            false,
            true
        );
    }
}
