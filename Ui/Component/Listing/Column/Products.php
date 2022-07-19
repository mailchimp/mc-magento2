<?php
namespace Ebizmarts\MailChimp\Ui\Component\Listing\Column;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;

class Products extends Column
{
    /**
     * @var ProductFactory
     */
    protected $_productFactory;
    /**
     * @var RequestInterface
     */
    protected $_requestInterface;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
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
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ProductFactory $productFactory
     * @param RequestInterface $requestInterface
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ProductFactory $productFactory,
        RequestInterface $requestInterface,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory,
        array $components = [],
        array $data = [])
    {
        $this->_productFactory = $productFactory;
        $this->_requestInterface = $requestInterface;
        $this->_helper = $helper;
        $this->_assetRepository = $assetRepository;
        $this->_mailChimpErrorsFactory = $mailChimpErrorsFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                /**
                 * @var $product \Magento\Catalog\Model\Product
                 */
                $product = $this->_productFactory->create()->load($item['entity_id']);
                $params = ['_secure' => $this->_requestInterface->isSecure()];
                $alt = '';
                if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE ||
                    $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
                    $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE ||
                    $product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
                    $url = '';
                    $text = '';
                    if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $product->getStoreId())) {
                        $mailchimpStoreId = $this->_helper->getConfigValue(
                            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                            $product->getStoreId()
                        );
                        $syncData = $this->_helper->getChimpSyncEcommerce(
                            $mailchimpStoreId,
                            $product->getId(),
                            \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
                        );
                        if (!$syncData || $syncData->getMailchimpStoreId() != $mailchimpStoreId ||
                            $syncData->getRelatedId() != $product->getId() ||
                            $syncData->getType() != \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT) {
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
                                    $alt = $syncData->getMailchimpSyncDelta();
                                    break;
                                case \Ebizmarts\MailChimp\Helper\Data::WAITINGSYNC:
                                    $url = $this->_assetRepository->getUrlWithParams(
                                        'Ebizmarts_MailChimp::images/waiting.png',
                                        $params
                                    );
                                    $text = __('Waiting');
                                    $alt = $syncData->getMailchimpSyncDelta();
                                    break;
                                case \Ebizmarts\MailChimp\Helper\Data::SYNCERROR:
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
                                case \Ebizmarts\MailChimp\Helper\Data::NEEDTORESYNC:
                                    $url = $this->_assetRepository->getUrlWithParams(
                                        'Ebizmarts_MailChimp::images/resync.png',
                                        $params
                                    );
                                    $text = __('Resyncing');
                                    $alt = $syncData->getMailchimpSyncDelta();
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
                                    $url = $this->_assetRepository->getUrlWithParams(
                                        'Ebizmarts_MailChimp::images/error.png',
                                        $params
                                    );
                                    $text = __('Error');
                            }
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
        return $error->getByStoreIdType($storeId, $productId, \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT);
    }
} 