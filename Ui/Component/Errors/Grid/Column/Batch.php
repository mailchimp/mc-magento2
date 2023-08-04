<?php

namespace Ebizmarts\MailChimp\Ui\Component\Errors\Grid\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Batch extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $edit = false;
                switch ($item['regtype']) {
                    case \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER:
                        $label = 'Edit customer';
                        $url = 'customer/index/edit';
                        $id = 'id';
                        $edit = true;
                        break;
                    case \Ebizmarts\MailChimp\Helper\Data::IS_ORDER:
                        $label = 'Edit order';
                        $url = 'sales/order/view';
                        $id = 'order_id';
                        $edit = true;
                        break;
                    case \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT:
                        $label = 'Edit product';
                        $url = 'catalog/product/edit';
                        $id = 'id';
                        $edit = true;
                        break;
                }
                $item[$this->getData('name')] = [
                    'download' => [
                        'href' => $this->urlBuilder->getUrl(
                            'mailchimp/errors/getresponse',
                            ['id' => $item['id']]
                        ),
                        'label' => 'Download Response'
                    ]
                ];
                if ($edit) {
                    $item[$this->getData('name')]['edit'] =
                        [
                            'href' => $this->urlBuilder->getUrl(
                                $url,
                                [$id => $item['original_id']]
                            ),
                            'label' => $label
                        ];
                }
            }
        }

        return $dataSource;
    }
}
