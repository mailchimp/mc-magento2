<?php

namespace Ebizmarts\MailChimp\Block;

use Magento\Framework\View\Element\Template;
use \Ebizmarts\MailChimp\Helper\Data as MailchimpHelper;

class Subscribe extends \Magento\Newsletter\Block\Subscribe
{
    /**
     * @var Template\Context
     */
    protected $context;
    /**
     * @var MailchimpHelper
     */
    protected $helper;

    /**
     * @param Template\Context $context
     * @param MailchimpHelper $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        MailchimpHelper $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->helper = $helper;
    }

    public function getPopupUrl()
    {

        $storeId = $this->context->getStoreManager()->getStore()->getId();
        return $this->helper->getConfigValue(MailchimpHelper::XML_POPUP_URL,$storeId);
    }
}