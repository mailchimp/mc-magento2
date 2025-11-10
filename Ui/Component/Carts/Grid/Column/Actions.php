<?php

namespace Ebizmarts\MailChimp\Ui\Component\Carts\Grid\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
class Actions extends Column
{
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
                $customerId = $item['customer_id'];
                if ($customerId) {
                    $item[$this->getData('name')] = [
                        'viewcustomer' => [
                            'href' => $this->urlBuilder->getUrl(
                                'customer/index/edit',
                                ['id' => $customerId]
                            ),
                            'label' => 'View Customer',
                            'target' => '_blank'
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
