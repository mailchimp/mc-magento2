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
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $encryptor;

    /**
     * ApiKey constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Encryption\Encryptor $encryptor
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->encryptor    = $encryptor;
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
    }
    public function toOptionArray()
    {
        $rc = [];
        if (is_array($this->options) && count($this->options)) {
            $rc[] = ['value' => -1, 'label' => 'Select one ApiKey'];
            foreach ($this->options as $apiKey) {
                $rc[] = ['value'=> $this->encryptor->encrypt(trim($apiKey)), 'label' => $this->mask(trim($apiKey))];
            }
        }
        return $rc;
    }
    public function getAllApiKeys()
    {
        $this->options = $this->helper->getAllApiKeys();
    }
    private function mask($str)
    {
        if (strlen($str) < 4) {
            return __('Invalid API Key');
        } else {
            return substr($str, 0, 6) . str_repeat('*', strlen($str) - 4) . substr($str, -4);
        }
    }
}
