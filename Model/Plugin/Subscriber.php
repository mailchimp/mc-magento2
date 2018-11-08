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
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $subscriber->getStoreId())) {
            $subscriber->loadByCustomerId($customerId);
            $api = $this->_api;
            try {
                $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
                $api->lists->members->update($this->_helper->getDefaultList(), $md5HashEmail, null, 'unsubscribed');
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }
        }
        return [$customerId];
    }

    public function beforeSubscribeCustomerById(
        $subscriber,
        $customerId
    ) {
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         */
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $subscriber->getStoreId())) {
            $subscriber->loadByCustomerId($customerId);
            if (!$subscriber->isSubscribed()) {
                if (!$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $subscriber->getStoreId())) {
                    $subscriber->setImportMode(true);
                }
                $storeId = $subscriber->getStoreId();
                if ($this->_helper->isMailChimpEnabled($storeId)) {
                    $customer = $this->_customer->getById($customerId);
                    $email = $customer->getEmail();
                    $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriber, $email);
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
                    } catch (\Mailchimp_Error $e) {
                        $this->_helper->log($e->getFriendlyMessage());
                    }
                }
            }
        }
        return [$customerId];
    }

    public function beforeSubscribe(
        $subscriber,
        $email
    ) {
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $subscriber->getStoreId())) {
            if (!$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $subscriber->getStoreId())) {
                $subscriber->setImportMode(true);
            }
            $storeId = $this->_storeManager->getStore()->getId();

            if ($this->_helper->isMailChimpEnabled($storeId)) {
                $api = $this->_api;
                if ($this->_helper->isDoubleOptInEnabled($storeId)) {
                    $status = 'pending';
                } else {
                    $status = 'subscribed';
                }
                $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriber, $email);
                try {
                    $md5HashEmail = md5(strtolower($email));
                    $return = $api->lists->members->addOrUpdate($this->_helper->getDefaultList(), $md5HashEmail, null, $status, $mergeVars, null, null, null, null, $email, $status);
                } catch (\Mailchimp_Error $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return [$email];
    }

    public function beforeUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
    
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $subscriber->getStoreId())) {
            $api = $this->_helper->getApi();
            try {
                $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
                $api->lists->members->update($this->_helper->getDefaultList(), $md5HashEmail, null, 'unsubscribed');
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }
        }
        return null;
    }
    public function afterDelete(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {
    
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $subscriber->getStoreId())) {
            $api = $this->_helper->getApi();
            if ($subscriber->isSubscribed()) {
                try {
                    $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
                    if ($subscriber->getCustomerId()) {
                        $api->lists->members->update($this->_helper->getDefaultList(), $md5HashEmail, null, 'unsubscribed');
                    } else {
                        $api->lists->members->delete($this->_helper->getDefaultList(), $md5HashEmail);
                    }
                } catch (\Mailchimp_Error $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return null;
    }
}
