<?php

namespace Ebizmarts\MailChimp\Model;

class MailChimpSyncEcommerceFactory
{

    protected $_objectManager;
    protected $_instanceName;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce::class
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
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
