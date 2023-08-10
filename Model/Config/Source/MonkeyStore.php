<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class MonkeyStore implements \Magento\Framework\Option\ArrayInterface
{
    private $options = null;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $storeId = (int)$request->getParam("store", 0);
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
                $this->options = $helper->getApi($storeId, $scope)->ecommerce->stores->get(
                    null,
                    null,
                    null,
                    \Ebizmarts\MailChimp\Helper\Data::MAXSTORES
                );
            } catch (\Mailchimp_Error $e) {
                $helper->log($e->getFriendlyMessage());
            }
        }
    }

    public function toOptionArray()
    {
        if (is_array($this->options)) {
            $rc = [];
            $rc[] = ['value' => -1, 'label' => 'Select one Mailchimp Store'];
            foreach ($this->options['stores'] as $store) {
                if ($store['platform'] == \Ebizmarts\MailChimp\Helper\Data::PLATFORM) {
                    if ($store['list_id'] == '') {
                        continue;
                    }
                    if (isset($store['connected_site'])) {
                        $label = $store['name'];
                    } else {
                        $label = $store['name'] . ' (Warning: not connected)';
                    }

                    $rc[] = ['value' => $store['id'], 'label' => $label];
                }
            }
        } else {
            $rc[] = ['value' => 0, 'label' => __('---No Data---')];
        }

        return $rc;
    }

    public function toArray()
    {
        $rc = [];
        foreach ($this->options['stores'] as $store) {
            $rc[$store['id']] = $store['name'];
        }

        return $rc;
    }
}
