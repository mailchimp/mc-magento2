<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 12/1/16 2:36 PM
 * @file: Collection.php
 */
namespace Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce::class,
            \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce::class
        );
    }
}
