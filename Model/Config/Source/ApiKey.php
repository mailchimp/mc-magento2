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
    private $options = null;
    /**
     * MonkeyStore constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        $apiKeys = $helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_APIKEY_LIST);
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
}
