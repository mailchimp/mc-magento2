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
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

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
        $this->_request         = $context->getRequest();
    }

    protected function _getMailchimpTags()
    {
        $ret = [];
        $storeId = (int) $this->_request->getParam("store", 0);
        if ($this->_request->getParam('website', 0)) {
            $scope = 'website';
            $storeId = $this->_request->getParam('website', 0);
        } elseif ($this->_request->getParam('store', 0)) {
            $scope = 'stores';
            $storeId = $this->_request->getParam('store', 0);
        } else {
            $scope = 'default';
        }

        $api = $this->_helper->getApi($storeId, $scope);
        try {
            $merge = $api->lists->mergeFields->getAll(
                $this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST,
                    $storeId,
                    $scope
                ),
                null,
                null,
                \Ebizmarts\MailChimp\Helper\Data::MAX_MERGEFIELDS
            );
            if (is_array($merge) && key_exists('merge_fields', $merge)) {
                foreach ($merge['merge_fields'] as $item) {
                    $ret[$item['tag']] = $item['tag'] . ' (' . $item['name'] . ' : ' . $item['type'] . ')';
                }
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
                $this->addOption($attId, $this->escapeHtmlAttr($attLabel));
            }
        }
        return parent::_toHtml();
    }
}
