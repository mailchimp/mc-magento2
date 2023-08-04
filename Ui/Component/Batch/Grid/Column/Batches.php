<?php

namespace Ebizmarts\MailChimp\Ui\Component\Batch\Grid\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Batches extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Helper
     */
    protected $mailChimpSyncB;
    /**
     * @var Helper
     */
    protected $helper;

    private $stores = [];

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpSyncBatches $mailChimpSyncB,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->helper = $helper;
        $this->mailChimpSyncB = $mailChimpSyncB;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function getDataSourceData()
    {
        return $this->getContext()->getDataProvider()->getData();
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$batch) {
                $batch_status = &$batch['status'];
                $batch_status = ucfirst($batch_status);
                $mailchimp_store_id = $batch['mailchimp_store_id'];
                $magentoStoreId = $batch['store_id'];
                $store_name = $this->getMCStoreNameById($mailchimp_store_id, $magentoStoreId);
                $batch['store_name'] = $store_name;
                $batch['subscribers'] = $batch['subscribers_new_count'] . " new;" . $batch["subscribers_modified_count"] . " modified";
                $batch['customers'] = $batch['customers_new_count'] . " new;" . $batch["customers_modified_count"] . " modified";
                $batch['orders'] = $batch['orders_new_count'] . " new;" . $batch["orders_modified_count"] . " modified";
                $batch['products'] = $batch['products_new_count'] . " new;" . $batch["products_modified_count"] . " modified";
                $batch['carts'] = $batch['carts_new_count'] . " new;" . $batch["carts_modified_count"] . " modified";
                $batch[$this->getData('name')] = [
                    'download' => [
                        'href' => $this->urlBuilder->getUrl(
                            'mailchimp/batch/getResponse',
                            ['id' => $batch['id']]
                        ),
                        'label' => 'Download'
                    ]
                ];
            }
        }

        return $dataSource;
    }

    private function getMCStoreNameById($mailchimp_store_id, $magentoStoreId)
    {
        if (!key_exists($mailchimp_store_id, $this->stores)) {
            $api = $this->helper->getApi($magentoStoreId);
            $store = $api->ecommerce->stores->get($mailchimp_store_id);
            $this->stores[$mailchimp_store_id] = $store['name'];
        }

        return $this->stores[$mailchimp_store_id];
    }
}
