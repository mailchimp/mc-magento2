<?php

namespace Ebizmarts\MailChimp\Ui\Component\Listing\Column;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;

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
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
     * @param UrlInterface $urlBuilder
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
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        $this->_assetRepository = $assetRepository;
        $this->_requestInterfase = $requestInterface;
        $this->_helper = $helper;
        $this->_syncCommerceCF = $syncCommerceCF;
        $this->_orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_mailChimpErrorsFactory = $mailChimpErrorsFactory;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {

            $orderMap = $this->getOrderDataForIncrementIds(
                $this->getOrderIncrementIds($dataSource)
            );

            foreach ($dataSource['data']['items'] as & $item) {
                $status = $item['mailchimp_flag'];
                $orderId = $item['increment_id'];
                $sync = $item['mailchimp_sent'];
                $error = $item['mailchimp_sync_error'];

                $order = $orderMap[$orderId]
                    ?? $this->_orderFactory->create()->loadByIncrementId($orderId); // Backwards Compatibility
                $menu = false;
                $params = ['_secure' => $this->_requestInterfase->isSecure()];
                $storeId = $order->getStoreId();
                if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
                    $alt = '';
                    switch ($sync) {
                        case \Ebizmarts\MailChimp\Helper\Data::NEVERSYNC:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/no.png',
                                $params
                            );
                            $text = __('Syncing');
                            break;
                        case \Ebizmarts\MailChimp\Helper\Data::SYNCED:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/yes.png',
                                $params
                            );
                            $text = __('Synced');
                            $menu = true;
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
                            $orderError = $this->_getError($orderId, $storeId);
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
                            $menu = true;
                            break;
                        case \Ebizmarts\MailChimp\Helper\Data::NOTSYNCED:
                            $url = $this->_assetRepository->getUrlWithParams(
                                'Ebizmarts_MailChimp::images/never.png',
                                $params
                            );
                            $text = __('With error');
                            $alt = $item['mailchimp_sync_error'];
                            break;
                        default:
                            $url = '';
                            $text = '';
                    }
                    $item['mailchimp_sync'] =
                        "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='" . $url . "' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' class='freddie'/>$text</div>";
                    if ($status) {
                        $item['mailchimp_sync'] =
                            "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='" . $url . "' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' class='freddie'/>$text</div>";
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
                            "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='" . $url . "' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt'/>$text</div>";
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
     * Extract Order Increment IDs for a given DataSource
     *
     * @param array $dataSource
     * @return array
     */
    private function getOrderIncrementIds(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return [];
        }

        return array_filter(array_unique(array_column($dataSource['data']['items'], 'increment_id')));
    }

    /**
     * @param array $incrementIds
     * @return OrderInterface[]
     */
    private function getOrderDataForIncrementIds(array $incrementIds): array
    {
        if (empty($incrementIds)) {
            return [];
        }

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->getSelect()->columns(['entity_id', 'increment_id', 'store_id']);
        $orderCollection->addAttributeToFilter(
            'increment_id',
            ['in' => $incrementIds]
        );

        $ordersMap = [];
        /** @var OrderInterface $order */
        foreach ($orderCollection->getItems() as $order) {
            $ordersMap[$order->getIncrementId()] = $order;
        }

        return $ordersMap;
    }
}
