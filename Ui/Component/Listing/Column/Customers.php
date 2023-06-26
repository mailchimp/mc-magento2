<?php

namespace Ebizmarts\MailChimp\Ui\Component\Listing\Column;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Model\CustomerFactory;
use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;

class Customers extends Column
{
    /**
     * @var RequestInterface
     */
    protected $_requestInterface;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    protected $_mailChimpErrorsFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RequestInterface $requestInterface
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param CustomerFactory $customerFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param SyncHelper $syncHelper
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RequestInterface $requestInterface,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        CustomerFactory $customerFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        SyncHelper $syncHelper,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $mailChimpErrorsFactory,
        array $components = [],
        array $data = [])
    {
        $this->_requestInterface = $requestInterface;
        $this->_assetRepository = $assetRepository;
        $this->_customerFactory = $customerFactory;
        $this->_helper          = $helper;
        $this->syncHelper       = $syncHelper;
        $this->_mailChimpErrorsFactory = $mailChimpErrorsFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $params = ['_secure' => $this->_requestInterface->isSecure()];
            foreach ($dataSource['data']['items'] as & $item) {
                /**
                 * @var $customer \Magento\Customer\Model\Customer
                 */
                $customer = $this->_customerFactory->create()->load($item['entity_id']);
                $params = ['_secure' => $this->_requestInterface->isSecure()];
                $alt = '';
                $url = '';
                $text = '';
                if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $customer->getStoreId())) {
                    $mailchimpStoreId = $this->_helper->getConfigValue(
                        \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
                        $customer->getStoreId()
                    );
                    $syncData = $this->syncHelper->getChimpSyncEcommerce(
                        $mailchimpStoreId,
                        $customer->getId(),
                        \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER
                    );
                    if (!$syncData || $syncData->getMailchimpStoreId() != $mailchimpStoreId ||
                        $syncData->getRelatedId() != $customer->getId() ||
                        $syncData->getType() != \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER) {
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
                                $customerError = $this->_getError($customer->getId(), $customer->getStoreId());
                                if ($customerError) {
                                    $alt = $customerError->getErrors();
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
                $item['mailchimp_sync'] =
                    "<div style='width: 100%;margin: 0 auto;text-align: center'><div><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' /></div><div>$text</div></div>";
            }
        }
        return $dataSource;
    }
    private function _getError($customerId, $storeId)
    {
        /**
         * @var $error \Ebizmarts\MailChimp\Model\MailChimpErrors
         */
        $error = $this->_mailChimpErrorsFactory->create();
        return $error->getByStoreIdType($storeId, $customerId, \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER);
    }

}
