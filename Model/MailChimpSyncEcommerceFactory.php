<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 2/28/17 7:24 PM
 * @file: MailChimpSyncEcommerceFactory.php
 */

namespace Ebizmarts\MailChimp\Model;

class MailChimpSyncEcommerceFactory
{

    protected $_objectManager;
    protected $_instanceName;

    /**
     * MailChimpSyncEcommerceFactory constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce::class
    ) {
    
        $this->_objectManager   = $objectManager;
        $this->_instanceName    = $instanceName;
    }

    /**
     * @param array $data
     * @return \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
