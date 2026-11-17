<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 9/30/16 2:46 PM
 * @file: Cmspage.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Source;

class Cmspage
{
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    private $_page;
    /**
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $page
     */
    public function __construct(
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $page
    ) {
        $this->_page = $page;
    }
    public function toOptionArray()
    {
        $pages = $this->_page->create()->addOrder('title', 'asc');
        return ['checkout/cart' => 'Shopping Cart (default page)'] + $pages->toOptionIdArray();
    }
}
