<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/9/17 5:20 PM
 * @file: SaveBefore.php
 */
namespace Ebizmarts\MailChimp\Observer\Subscriber;

use Magento\Framework\Event\Observer;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
     */
    protected $_ecommerce;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Subscriber
     */
    protected $_subscriberApi;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * SaveBefore constructor.
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Api\Subscriber $subscriberApi
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $ecommerce,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Subscriber $subscriberApi,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {

        $this->_ecommerce           = $ecommerce;
        $this->_helper              = $helper;
        $this->_subscriberApi       = $subscriberApi;
        $this->_subscriberFactory   = $subscriberFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         */
        $factory = $this->_subscriberFactory->create();
        $subscriber = $observer->getSubscriber();
        $subscriberOld = $factory->loadByCustomerId($subscriber->getCustomerId());

        if ($this->_helper->isMailChimpEnabled($subscriberOld->getStoreId())&&$subscriberOld->getEmail()&&$subscriber->getEmail()!=$subscriberOld->getEmail()) {
            $api = $this->_helper->getApi($subscriberOld->getStoreId());
            $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriberOld, $subscriberOld->getEmail());
            $status = 'unsubscribed';
            try {
                $md5HashEmail = hash('md5', strtolower($subscriberOld->getEmail()));
                $return = $api->lists->members->addOrUpdate(
                    $this->_helper->getDefaultList($subscriberOld->getStoreId()),
                    $md5HashEmail,
                    null,
                    $status,
                    $mergeVars,
                    null,
                    null,
                    null,
                    null,
                    $subscriberOld->getEmail(),
                    $status
                );
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }

        }
        $this->_subscriberApi->update($subscriber);
    }
}
