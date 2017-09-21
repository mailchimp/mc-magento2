<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Model\Plugin;

class Subscriber
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
     * @param \Magento\Customer\Model\Session $customerSession
     */
    protected $_api = null;

    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
    
        $this->_helper          = $helper;
        $this->_customer        = $customer;
        $this->_customerSession = $customerSession;
        $this->_storeManager    = $storeManager;
        $this->_api             = $this->_helper->getApi();
    }

    public function beforeUnsubscribeCustomerById(
        $subscriber,
        $customerId
    ) {
        $subscriber->loadByCustomerId($customerId);
        $api = $this->_api;
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($this->_helper->getDefaultList(), $md5HashEmail, null, 'unsubscribed');
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return [$customerId];
    }

    public function beforeSubscribeCustomerById(
        $subscriber,
        $customerId
    ) {
        $subscriber->loadByCustomerId($customerId);
        $subscriber->setImportMode(true);
        $storeId = $subscriber->getStoreId();
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $customer = $this->_customer->getById($customerId);
            $email = $customer->getEmail();
            $mergeVars = $this->_helper->getMergeVars($customer, $email);
            $api = $this->_api;
            $isSubscribeOwnEmail = $this->_customerSession->isLoggedIn()
                && $this->_customerSession->getCustomerDataObject()->getEmail() == $subscriber->getSubscriberEmail();
            if ($this->_helper->isDoubleOptInEnabled($storeId) && !$isSubscribeOwnEmail) {
                $status = 'pending';
            } else {
                $status = 'subscribed';
            }
            try {
                $emailHash = md5(strtolower($customer->getEmail()));
                if (!$subscriber->getMailchimpId()) {
                    $return = $api->lists->members->addOrUpdate($this->_helper->getDefaultList(), $emailHash, null, $status, $mergeVars, null, null, null, null, $email, $status);
                }
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return [$customerId];
    }

    public function beforeSubscribe(
        $subscriber,
        $email
    ) {
        $storeId = $this->_storeManager->getStore()->getId();

        /**
         * Set storeId in $subscriber because if customer subscribe newsletter as a guest, storeId is null and generate error "Data did not match any of the schemas described in anyOf."
         */
        if(!$subscriber->getStoreId()) {
            $subscriber->setStoreId($storeId);
        }

        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $api = $this->_api;
            if ($this->_helper->isDoubleOptInEnabled($storeId)) {
                $status = 'pending';
            } else {
                $status = 'subscribed';
            }
            $mergeVars = $this->_helper->getMergeVars($subscriber, $email);
            try {
                $md5HashEmail = md5(strtolower($email));
                $return = $api->lists->members->addOrUpdate($this->_helper->getDefaultList(), $md5HashEmail, null, $status, $mergeVars, null, null, null, null, $email, $status);
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return [$email];
    }

    public function beforeUnsubscribe(
        $subscriber
    )
    {
            $api = $this->_helper->getApi();
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($this->_helper->getDefaultList(), $md5HashEmail, null, 'unsubscribed');
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return null;
    }
}
