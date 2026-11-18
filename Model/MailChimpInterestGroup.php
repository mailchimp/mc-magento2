<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/20/17 3:43 PM
 * @file: MailChimpInterestGroup.php
 */

namespace Ebizmarts\MailChimp\Model;

class MailChimpInterestGroup extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup
     */
    protected $mailChimpInterestGroupResource;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup $mailChimpInterestGroupResource,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mailChimpInterestGroupResource = $mailChimpInterestGroupResource;
        parent::__construct($context, $registry, $resource ?? $mailChimpInterestGroupResource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Ebizmarts\MailChimp\Model\ResourceModel\MailChimpInterestGroup::class);
    }
    public function getBySubscriberIdStoreId($subscriberId, $storeId)
    {
        $this->mailChimpInterestGroupResource->getBySubscriberIdStoreId($this, $subscriberId, $storeId);
        return $this;
    }
}
