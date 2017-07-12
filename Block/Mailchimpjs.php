<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/8/17 12:00 PM
 * @file: Mailchimpjs.php
 */

namespace Ebizmarts\MailChimp\Block;

class Mailchimpjs extends \Magento\Framework\View\Element\Template
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
     * Mailchimpjs constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data
    ) {
    
        parent::__construct($context, $data);
        $this->_helper          = $helper;
        $this->_storeManager    = $context->getStoreManager();
    }

    public function getJsUrl()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        return $this->_helper->getJsUrl($storeId);
    }
}
