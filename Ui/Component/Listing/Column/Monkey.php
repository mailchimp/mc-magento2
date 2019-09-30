<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/15/17 1:23 AM
 * @file: Monkey.php
 */
namespace Ebizmarts\MailChimp\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\StoreManagerInterface;

class Monkey extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteria;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_requestInterfase;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory
     */
    protected $_syncCommerceCF;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    protected $_mailChimpErrorsFactory;

    /**
     * Monkey constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param SearchCriteriaBuilder $criteria
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $syncCommerceCF
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\App\RequestInterface $requestInterface,
        SearchCriteriaBuilder $criteria,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $syncCommerceCF,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $components = [],
        array $data = []
    ) {
    
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_assetRepository = $assetRepository;
        $this->_requestInterfase= $requestInterface;
        $this->_helper          = $helper;
        $this->_syncCommerceCF  = $syncCommerceCF;
        $this->_orderFactory    = $orderFactory;
        $this->_mailChimpErrorsFactory  = $mailChimpErrorsFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $status = $item['mailchimp_flag'];
                $order = $this->_orderFactory->create()->loadByIncrementId($item['increment_id']);
                $params = ['_secure' => $this->_requestInterfase->isSecure()];
                if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $order->getStoreId())) {
                    $mailchimpStoreId = $this->_helper->getConfigValue(
                        \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                        $order->getStoreId()
                    );
                    $syncData = $this->_helper->getChimpSyncEcommerce(
                        $mailchimpStoreId,
                        $order->getId(),
                        \Ebizmarts\MailChimp\Helper\Data::IS_ORDER
                    );
                    $alt = '';
                    if (!$syncData || $syncData->getMailchimpStoreId() != $mailchimpStoreId ||
                        $syncData->getRelatedId() != $order->getId() ||
                        $syncData->getType() != \Ebizmarts\MailChimp\Helper\Data::IS_ORDER) {
                        $url = $this->_assetRepository->getUrlWithParams(
                            'Ebizmarts_MailChimp::images/no.png',
                            $params
                        );
                        $text = __('Syncing');
                    } else {
                        $sync = $syncData->getMailchimpSent();
                        switch ($sync) {
                            case \Ebizmarts\MailChimp\Helper\Data::SYNCED:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/yes.png',
                                    $params
                                );
                                $text = __('Synced');
                                break;
                            case \Ebizmarts\MailChimp\Helper\Data::WAITINGSYNC:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/waiting.png',
                                    $params
                                );
                                $text = __('Waiting');
                                break;
                            case \Ebizmarts\MailChimp\Helper\Data::SYNCERROR:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/error.png',
                                    $params
                                );
                                $text = __('Error');
                                $orderError = $this->_getError($order->getId(), $order->getStoreId());
                                if ($orderError) {
                                    $alt = $orderError->getErrors();
                                }
                                break;
                            case \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/resync.png',
                                    $params
                                );
                                $text = __('Resyncing');
                                break;
                            case \Ebizmarts\MailChimp\Helper\Data::NOTSYNCED:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/never.png',
                                    $params
                                );
                                $text = __('With error');
                                $alt = $syncData->getMailchimpSyncError();
                                break;
                            default:
                                $url ='';
                                $text = '';
                        }
                    }
                    $item['mailchimp_sync'] =
                        "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' />$text</div>";
                    if ($status) {
                        $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_MailChimp::images/freddie.png', $params);
                        $item['mailchimp_status'] =
                            "<div style='width: 50%;margin: 0 auto'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%'/></div>";
                    }
                }
            }
        }

        return $dataSource;
    }
    private function _getError($orderId, $storeId)
    {
        /**
         * @var $error \Ebizmarts\MailChimp\Model\MailChimpErrors
         */
        $error = $this->_mailChimpErrorsFactory->create();
        return $error->getByStoreIdType($storeId, $orderId, \Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
    }
}
