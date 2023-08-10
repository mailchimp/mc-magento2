<?php

namespace Ebizmarts\MailChimp\Model\Config\Source;

class Cmspage
{
    /**
     * @var \Magento\Cms\Model\Page
     */
    private $_page;

    /**
     * @param \Magento\Cms\Model\Page $page
     */
    public function __construct(
        \Magento\Cms\Model\Page $page
    ) {
        $this->_page = $page;
    }

    public function toOptionArray()
    {
        $pages = $this->_page->getCollection()->addOrder('title', 'asc');

        return ['checkout/cart' => 'Shopping Cart (default page)'] + $pages->toOptionIdArray();
    }
}
