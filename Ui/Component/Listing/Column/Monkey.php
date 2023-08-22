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
use Magento\Framework\UrlInterface;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory as SyncCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\Collection as SyncCollection;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

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
     * @var MailChimpHelper
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
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    protected $_mailChimpErrorsFactory;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var SyncCollectionFactory
     */
    private $syncCollectionFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\App\RequestInterface $requestInterface,
        SearchCriteriaBuilder $criteria,
        MailChimpHelper $helper,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $syncCommerceCF,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        UrlInterface $urlBuilder,
        SyncCollectionFactory $syncCollectionFactory,
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
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_mailChimpErrorsFactory  = $mailChimpErrorsFactory;
        $this->urlBuilder       = $urlBuilder;
        $this->syncCollectionFactory = $syncCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $ordersIds = $this->getOrdersIds($dataSource);
            $orderMap = $this->getOrderDataByOrderIds($ordersIds);
            $syncMap = $this->getSyncDataByOrderIds($ordersIds);
            foreach ($dataSource['data']['items'] as & $item) {
                $status = $item['mailchimp_flag'];
                $orderId = $item['entity_id'];
                if (key_exists($item['entity_id'],$syncMap)) {
                    $sync = $syncMap[$item['entity_id']]->getMailchimpSent();
                    $syncError = $syncMap[$item['entity_id']]->getMailchimpSyncError();
                } else {
                    $sync = MailChimpHelper::NEVERSYNC;
                    $syncError = '';
                }

                $order = $orderMap[$orderId]
                    ?? $this->_orderFactory->create()->loadByIncrementId($orderId); // Backwards Compatibility
                $menu = false;
                $params = ['_secure' => $this->_requestInterfase->isSecure()];
                $storeId = $order->getStoreId();
                if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
                    $alt = '';
                    switch ($sync) {
                        case MailChimpHelper::NEVERSYNC:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/no.png',
                                $params
                            );
                            $text = __('Syncing');
                            break;
                        case MailChimpHelper::SYNCED:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/yes.png',
                                $params
                            );
                            $text = __('Synced');
                            $menu = true;
                            break;
                        case MailChimpHelper::WAITINGSYNC:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/waiting.png',
                                $params
                            );
                            $text = __('Waiting');
                            break;
                        case MailChimpHelper::SYNCERROR:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/error.png',
                                $params
                            );
                            $text = __('Error');
                            $orderError = $this->_getError($orderId, $storeId);
                            if ($orderError) {
                                $alt = $orderError->getErrors();
                            }
                            break;
                        case MailChimpHelper::NEEDTORESYNC:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/resync.png',
                                $params
                            );
                            $text = __('Resyncing');
                            $menu = true;
                            break;
                        case MailChimpHelper::NOTSYNCED:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/never.png',
                                $params
                            );
                            $text = __('With error');
                            $alt = $syncError;
                            break;
                        default:
                            $url ='';
                            $text = '';
                    }
                    $item['mailchimp_sync'] =
                        "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' class='freddie'/>$text</div>";
                    if ($status) {
                        $item['mailchimp_sync'] =
                            "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' class='freddie'/>$text</div>";
                        if ($menu) {
                            $item[$this->getData('name')] = [
                                'campaign' => [
                                    'href' => $this->urlBuilder->getUrl(
                                        'mailchimp/orders/campaign',
                                        ['orderId' => $item['entity_id']]
                                    ),
                                    'label' => 'View campaign',
                                    'target' => '_blank'
                                ],
                                'member' => [
                                    'href' => $this->urlBuilder->getUrl(
                                        'mailchimp/orders/member',
                                        ['orderId' => $item['entity_id']]
                                    ),
                                    'label' => 'View member',
                                    'target' => '_blank'
                                ]
                            ];
                        }
                    } else {
                        $item['mailchimp_sync'] =
                            "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt'/>$text</div>";
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

    /**
     * @param array $orderIds
     * @return OrderInterface[]
     */
    private function getOrderDataByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->getSelect()->columns(['entity_id','store_id']);
        $orderCollection->addAttributeToFilter(
            'entity_id',
            ['in' => $orderIds]
        );

        $ordersMap = [];
        /** @var OrderInterface $order */
        foreach ($orderCollection->getItems() as $order) {
            $ordersMap[$order->getEntityId()] = $order;
        }

        return $ordersMap;
    }
    private function getOrdersIds(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return [];
        }

        return array_filter(array_unique(array_column($dataSource['data']['items'], 'entity_id')));
    }

    private function getSyncDataByOrderIds(array $OrderIds)
    {
        $syncMap = [];
        /**
         * @var SyncCollection $syncCollection
         */
        $syncCollection = $this->syncCollectionFactory->create();
        $syncCollection->addFieldToFilter('related_id', ['in' => $OrderIds]);
        $syncCollection->addFieldToFilter('type', ['eq' => MailChimpHelper::IS_ORDER]);
        foreach($syncCollection as $item) {
            $syncMap[$item->getRelatedId()] = $item;
        }
        return $syncMap;
    }
}
