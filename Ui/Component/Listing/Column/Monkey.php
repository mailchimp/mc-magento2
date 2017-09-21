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
     * Monkey constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param SearchCriteriaBuilder $criteria
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
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
        array $components = [],
        array $data = []
    ) {
    
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->_assetRepository = $assetRepository;
        $this->_requestInterfase= $requestInterface;
        $this->_helper          = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $status = $item['mailchimp_flag'];
                $fieldName = $this->getData('name');

                switch ($status) {
                    case "0":
                        $item[$fieldName . '_src'] = '';
                        $item[$fieldName . '_alt'] = '';
                        $item[$fieldName . '_link'] = '';
                        $item[$fieldName . '_orig_src'] = '';
                        $item[$fieldName . '_class'] = '';
                        break;
                    case "1":
                        $params = ['_secure' => $this->_requestInterfase->isSecure()];
                        $url = $this->_assetRepository->getUrlWithParams('Ebizmarts_MailChimp::images/logo-freddie-monocolor-200.png', $params);
                        $item[$fieldName . '_src'] = $url;
                        $item[$fieldName . '_alt'] = 'hep hep thanks MailChimp';
                        $item[$fieldName . '_link'] = '';
                        break;
                }
            }
        }

        return $dataSource;
    }
}
