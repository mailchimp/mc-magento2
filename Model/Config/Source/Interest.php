<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class Interest implements \Magento\Framework\Option\ArrayInterface
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
                $this->options = $helper->getApi($storeId, $scope)->lists->interestCategory->getAll(
                    $helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId, $scope),
                    null,
                    null,
                    200
                );
            } catch (\Mailchimp_Error $e) {
                $helper->log($e->getFriendlyMessage());
            }
        }
    }

    public function toOptionArray()
    {
        if (is_array($this->options) && is_array($this->options['categories']) && count($this->options['categories'])) {
            $rc = [];
            foreach ($this->options['categories'] as $interest) {
                $rc[] = ['value' => $interest['id'], 'label' => $interest['title']];
            }
        } else {
            $rc[] = ['value' => [], 'label' => __('---No Data---')];
        }

        return $rc;
    }

    public function toArray()
    {
        $rc = [];
        $rc[$this->options['id']] = $this->options['name'];

        return $rc;
    }
}
