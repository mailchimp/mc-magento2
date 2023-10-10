<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Ebizmarts\MailChimp\Helper\Data as MailchimpHelper;
use Magento\Framework\App\RequestInterface;
class Maps implements OptionSourceInterface
{
    private $options = null;
    public function __construct(
        RequestInterface $request,
        MailchimpHelper $helper
    )
    {
        $storeId = (int) $request->getParam("store", 0);
        if ($request->getParam('website', 0)) {
            $scope = 'website';
            $storeId = $request->getParam('website', 0);
        } elseif ($request->getParam('store', 0)) {
            $scope = 'stores';
            $storeId = $request->getParam('store', 0);
        } else {
            $scope = 'default';
        }

        if ($helper->getApiKey($storeId, $scope)) {
            try {
                $this->options = $helper->getApi($storeId, $scope)->lists->mergeFields->getAll(
                    $helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId, $scope),
                    null,
                    null,
                    MailchimpHelper::MAX_MERGEFIELDS
                );
            } catch (\Mailchimp_Error $e) {
                $helper->log($e->getFriendlyMessage());
            }
        }

    }
    public function toOptionArray()
    {
        if (is_array($this->options)&&key_exists('merge_fields', $this->options)) {
            $rc = [];
            foreach ($this->options['merge_fields'] as $item) {
                $rc[$item['tag']] = $item['tag'] . ' (' . $item['name'] . ' : ' . $item['type'] . ')';
            }
        } else {
            $rc[] = ['value' => 0, 'label' => __('---No Data---')];
        }
        return $rc;
    }
}