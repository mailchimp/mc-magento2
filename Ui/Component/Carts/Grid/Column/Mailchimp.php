<?php

namespace Ebizmarts\MailChimp\Ui\Component\Carts\Grid\Column;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;

class Mailchimp extends Column
{
    /**
     * @var Repository
     */
    protected $_assetRepository;
    protected $_requestInterfase;
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        MailChimpHelper $mailChimpHelper,
        Repository $assetRepository,
        RequestInterface $requestInterfase,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->_assetRepository = $assetRepository;
        $this->_requestInterfase = $requestInterfase;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $params = ['_secure' => $this->_requestInterfase->isSecure()];
                $sync = $item['mailchimp_sent'];
                $alt = '';
                switch ($sync) {
                    case MailChimpHelper::NEVERSYNC:
                        $url = $this->_assetRepository->getUrlWithParams(
                            'Ebizmarts_MailChimp::images/no.png',
                            $params
                        );
                        $text = __('No Syncing');
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
                        break;
                    default:
                        $url ='';
                        $text = '';
                }
                $item[$this->getData('name')] = "<div style='width: 50%;margin: 0 auto;text-align: center'><img src='".$url."' style='border: none; width: 5rem; text-align: center; max-width: 100%' title='$alt' class='freddie'/>$text</div>";
            }
        }
        return $dataSource;
    }

}
