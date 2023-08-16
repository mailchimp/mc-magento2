<?php
namespace Ebizmarts\MailChimp\Ui\Component\Listing\Column;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ProductTypeConfigurable;
use Magento\Downloadable\Model\Product\Type as ProductTypeDownloadable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory as SyncCollectionFactory;
use Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\Collection as SyncCollection;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

class Products extends Column
{
    private const SUPPORTED_PRODUCT_TYPES = [
        ProductType::TYPE_SIMPLE,
        ProductType::TYPE_VIRTUAL,
        ProductTypeConfigurable::TYPE_CODE,
        ProductTypeDownloadable::TYPE_DOWNLOADABLE
    ];

    /**
     * @var ProductFactory
     */
    protected $_productFactory;
    /**
     * @var RequestInterface
     */
    protected $_requestInterface;
    /**
     * @var MailChimpHelper
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    protected $_mailChimpErrorsFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;
    /**
     * @var SyncCollectionFactory
     */
    private $syncCollectionFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ProductFactory $productFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param RequestInterface $requestInterface
     * @param MailChimpHelper $helper
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory
     * @param SyncCollectionFactory $syncCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ProductFactory $productFactory,
        ProductCollectionFactory $productCollectionFactory,
        RequestInterface $requestInterface,
        MailChimpHelper $helper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory,
        SyncCollectionFactory $syncCollectionFactory,
        array $components = [],
        array $data = [])
    {
        $this->_productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_requestInterface = $requestInterface;
        $this->_helper = $helper;
        $this->_assetRepository = $assetRepository;
        $this->_mailChimpErrorsFactory = $mailChimpErrorsFactory;
        $this->syncCollectionFactory = $syncCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {

            $productsMap = $this->getProductsByEntityIds($this->getProductsIds($dataSource));
            $syncMap = $this->getSyncDataByEntityIds($this->getProductsIds($dataSource));

            foreach ($dataSource['data']['items'] as & $item) {
                /**
                 * @var $product \Magento\Catalog\Model\Product
                 */
                $product = $productsMap[$item['entity_id']]
                    ?? $this->_productFactory->create()->load($item['entity_id']); // Backwards Compatibility
                if (key_exists($item['entity_id'],$syncMap)) {
                    $sync = $syncMap[$item['entity_id']]->getMailchimpSent();
                } else {
                    $sync = MailChimpHelper::NEVERSYNC;
                }

                $params = ['_secure' => $this->_requestInterface->isSecure()];
                $alt = '';
                $url = '';
                $text = '';
                if (in_array($product->getTypeId(), self::SUPPORTED_PRODUCT_TYPES, true)) {
                    if ($this->_helper->getConfigValue(MailChimpHelper::XML_PATH_ACTIVE, $product->getStoreId())) {
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
                                $orderError = $this->_getError($product->getId(), $product->getStoreId());
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
                                break;
                            case MailChimpHelper::NOTSYNCED:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/never.png',
                                    $params
                                );
                                $text = __('With error');
                                break;
                            default:
                                $url = $this->_assetRepository->getUrlWithParams(
                                    'Ebizmarts_MailChimp::images/error.png',
                                    $params
                                );
                                $text = __('Error');
                        }
                    }
                } else {
                    $url = $this->_assetRepository->getUrlWithParams(
                        'Ebizmarts_MailChimp::images/never.png',
                        $params
                    );
                    $text = __('Unsupported');
                    $alt = "Mailchimp does not support bundled or grouped products.";
                }
                $item['mailchimp_sync'] =
                    "<div style='width: 100%;margin: 0 auto;text-align: center'><div><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' /></div><div>$text</div></div>";
            }
        }
        return $dataSource;
    }
    private function _getError($productId, $storeId)
    {
        /**
         * @var $error \Ebizmarts\MailChimp\Model\MailChimpErrors
         */
        $error = $this->_mailChimpErrorsFactory->create();
        return $error->getByStoreIdType($storeId, $productId, MailChimpHelper::IS_PRODUCT);
    }

    private function getProductsByEntityIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productsCollection */
        $productsCollection = $this->productCollectionFactory->create();
        $productsCollection->addAttributeToFilter('entity_id', ['in' => $productIds]);

        $productsMap = [];
        foreach ($productsCollection->getItems() as $product) {
            $productsMap[$product->getId()] = $product;
        }

        return $productsMap;
    }

    private function getProductsIds(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return [];
        }

        return array_filter(array_unique(array_column($dataSource['data']['items'], 'entity_id')));
    }
    private function getSyncDataByEntityIds(array $productIds)
    {
        $syncMap = [];
        /**
         * @var SyncCollection $syncCollection
         */
        $syncCollection = $this->syncCollectionFactory->create();
        $syncCollection->addFieldToFilter('related_id', ['in' => $productIds]);
        $syncCollection->addFieldToFilter('type', ['eq' => MailChimpHelper::IS_PRODUCT]);
        foreach($syncCollection as $item) {
            $syncMap[$item->getRelatedId()] = $item;
        }
        return $syncMap;
    }
}
