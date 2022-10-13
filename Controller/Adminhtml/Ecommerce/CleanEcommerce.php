<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 2/21/17 5:07 PM
 * @file: ResetLocalErrors.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Ecommerce;

use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\ValidatorException;
use Symfony\Component\Config\Definition\Exception\Exception;

class CleanEcommerce extends \Magento\Backend\App\Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    protected $chimpSyncEcommerce;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $collectionFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $collectionFactory,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        parent::__construct($context);
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->helper               = $helper;
        $this->collectionFactory    = $collectionFactory;
        $this->chimpSyncEcommerce   = $chimpSyncEcommerce;
    }

    public function execute()
    {
        $valid = 1;
        $message = '';

        $resultJson = $this->resultJsonFactory->create();
        try {
            $collection = $this->collectionFactory->create();
            $collection->getSelect()->joinLeft(
                ['core_config' => $this->helper->getTableName('core_config_data')],
                'value = mailchimp_store_id'
            );
            $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['mailchimp_store_id']);
            $collection->getSelect()->where('value is null');
            $collection->getSelect()->group('mailchimp_store_id');
            foreach($collection as $item) {
                $mailchimpStoreId = $item->getMailchimpStoreId();
                $connection = $this->chimpSyncEcommerce->getResource()->getConnection();
                $tableName = $this->chimpSyncEcommerce->getResource()->getMainTable();
                $connection->delete(
                    $tableName,
                    "mailchimp_store_id = '$mailchimpStoreId'"
                );
            }
        } catch (ValidatorException $e) {
            $valid = 0;
            $message = $e->getMessage();
        }
        return $resultJson->setData([
            'valid' => (int)$valid,
            'message' => $message,
        ]);
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::config_mailchimp');
    }
}
