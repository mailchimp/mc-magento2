<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/9/18 1:17 PM
 * @file: MailchimpMap.php
 */

namespace Ebizmarts\MailChimp\Block\Adminhtml\System\Config\Form\Field;

class MailchimpMap extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * MailchimpMap constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data = []
    ) {
    
        parent::__construct($context, $data);
        $this->_helper          = $helper;
        $this->_storeManager    = $storeManager;
    }

    protected function _getMailchimpTags()
    {
        $ret = [];
        $api = $this->_helper->getApi($this->_storeManager->getStore()->getId());
        try {
            $merge = $api->lists->mergeFields->getAll($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST));
            foreach ($merge['merge_fields'] as $item) {
                $ret[$item['tag']] = $item['tag'] . ' (' . $item['name'] . ' : ' . $item['type'] . ')';
            }
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }
        return $ret;
    }
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getMailchimpTags() as $attId => $attLabel) {
                $this->addOption($attId, addslashes($attLabel));
            }
        }
        return parent::_toHtml();
    }
}
