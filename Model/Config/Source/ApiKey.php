<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/30/17 1:36 PM
 * @file: ApiKey.php
 */
namespace Ebizmarts\MailChimp\Model\Config\Source;

class ApiKey implements \Magento\Framework\Option\ArrayInterface
{
    protected $options = null;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;

    /**
     * ApiKey constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
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
        $apiKeys = $helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY_LIST, $storeId, $scope);
        if ($apiKeys) {
            $this->options = explode("\n", $apiKeys);
        } else {
            $this->options = [];
        }
    }
    public function toOptionArray()
    {
        if (is_array($this->options) && count($this->options)) {
            $rc = [];
            $rc[] = ['value' => -1, 'label' => 'Select one ApiKey'];
            foreach ($this->options as $apiKey) {
                    $rc[] = ['value'=> trim($apiKey), 'label' => trim($apiKey)];
            }
        } else {
            $rc[] = ['value' => 0, 'label' => __('---Enter first an APIKey list---')];
        }
        return $rc;
    }
    public function getAllApiKeys()
    {
        $this->options = $this->helper->getAllApiKeys();
    }
}
