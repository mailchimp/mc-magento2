<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 9/30/16 12:09 PM
 * @file: Monkeylist.php
 */

namespace Ebizmarts\MailChimp\Model\Config\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Monkeylist extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $_date;

    /**
     * Monkeylist constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->resourceConfig = $resourceConfig;
        $this->_date = $date;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $generalData = $this->getData();
        $data = $this->getData('groups');
        if (isset($data['ecommerce']['fields']['active']['value'])) {
            $active = $data['ecommerce']['fields']['active']['value'];
        } elseif ($data['ecommerce']['fields']['active']['inherit']) {
            $active = $data['ecommerce']['fields']['active']['inherit'];
        }
        if ($active&&$this->isValueChanged()) {
            if ($this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE,$generalData['scope_id'])) {
                $this->_helper->deleteStore();
            }
            $store = $this->_helper->createStore($this->getValue(), $generalData['scope_id']);
            if ($store) {
                $this->resourceConfig->saveConfig(
                    \Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE, $store,
                    $generalData['scope'],
                    $generalData['scope_id']
                );
                $this->resourceConfig->saveConfig(
                    \Ebizmarts\MailChimp\Helper\Data::XML_PATH_SYNC_DATE,
                    $this->_date->gmtDate(),
                    $generalData['scope'],
                    $generalData['scope_id']
                );
            }
        }
        return parent::beforeSave();
    }
}
