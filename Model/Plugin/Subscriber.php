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

    /**
     * Subscriber constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customer
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
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
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeUnsubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $customerId
    ) {
        $storeId = $this->getStoreIdFromSubscriber($subscriber);
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            if (!$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $storeId)) {
                $subscriber->setImportMode(true);
            }
            $subscriber->loadByCustomerId($customerId);
            if ($subscriber->isSubscribed()) {
                $api = $this->_helper->getApi($storeId);
                try {
                    $md5HashEmail = hash('md5', strtolower($subscriber->getSubscriberEmail()));
                    $api->lists->members->update(
                        $this->_helper->getDefaultList($storeId),
                        $md5HashEmail,
                        null,
                        'unsubscribed'
                    );
                } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return [$customerId];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSubscribeCustomerById(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $customerId
    ) {

        $storeId = $this->getStoreIdFromSubscriber($subscriber);
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $subscriber->loadByCustomerId($customerId);
            if (!$subscriber->isSubscribed()) {
                if (!$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $storeId)) {
                    $subscriber->setImportMode(true);
                }
                if ($this->_helper->isMailChimpEnabled($storeId)) {
                    $customer = $this->_customer->getById($customerId);
                    $email = $customer->getEmail();
                    $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriber, $email);
                    $api = $this->_helper->getApi($storeId);
                    if ($this->_helper->isDoubleOptInEnabled($storeId)) {
                        $status = 'pending';
                    } else {
                        $status = 'subscribed';
                    }
                    try {
                        $emailHash = hash('md5', strtolower($customer->getEmail()));
                        $api->lists->members->addOrUpdate(
                            $this->_helper->getDefaultList($storeId),
                            $emailHash,
                            null,
                            $status,
                            $mergeVars,
                            null,
                            null,
                            null,
                            null,
                            $email,
                            $status
                        );
                    } catch (\Mailchimp_Error $e) {
                        $this->_helper->log($e->getFriendlyMessage());
                    }
                }
            }
        }
        return [$customerId];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $email
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        $email
    ) {

        $storeId = $this->getStoreIdFromSubscriber($subscriber);
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            if (!$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $storeId)) {
                $subscriber->setImportMode(true);
            }
            $storeId = $this->_storeManager->getStore()->getId();

            if ($this->_helper->isMailChimpEnabled($storeId)) {
                $api = $this->_helper->getApi($storeId);
                if ($this->_helper->isDoubleOptInEnabled($storeId)) {
                    $status = 'pending';
                } else {
                    $status = 'subscribed';
                }
                $mergeVars = $this->_helper->getMergeVarsBySubscriber($subscriber, $email);
                try {
                    $md5HashEmail = hash('md5', strtolower($email));
                    $return = $api->lists->members->addOrUpdate(
                        $this->_helper->getDefaultList($storeId),
                        $md5HashEmail,
                        null,
                        $status,
                        $mergeVars,
                        null,
                        null,
                        null,
                        null,
                        $email,
                        $status
                    );
                } catch (\Mailchimp_Error $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return [$email];
    }

    /**
     * Mirror an unsubscribe to Mailchimp, whatever else the platform does next.
     *
     * `around` rather than a before/after pair, with the mirror in a `finally`.
     * The platform saves the row and then sends its own email, so a mail
     * transport that raises leaves Magento unsubscribed and would leave an
     * `after` plugin unreached -- the shopper is out on this side and still
     * subscribed on Mailchimp's, which is the outcome worth engineering
     * against. `finally` runs either way and the exception propagates
     * untouched.
     *
     * Import mode stays ahead of the platform, where it has to be: it is what
     * suppresses Magento's own email.
     *
     * Nothing is assumed about whether anything happened -- the status is read
     * after the fact. A mismatched confirmation code raises before the row is
     * touched, so the status is still subscribed and nothing is mirrored.
     *
     * @param  \Magento\Newsletter\Model\Subscriber $subscriber
     * @param  callable $proceed
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        callable $proceed
    ) {
        $storeId = $this->getStoreIdFromSubscriber($subscriber);
        if ($this->_helper->isMailChimpEnabled($storeId)
            && !$this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL, $storeId)
        ) {
            $subscriber->setImportMode(true);
        }

        try {
            return $proceed();
        } finally {
            $this->mirrorUnsubscribe($subscriber);
        }
    }

    /**
     * Tell Mailchimp the address is out, if the row says it is.
     *
     * @param  \Magento\Newsletter\Model\Subscriber $subscriber
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function mirrorUnsubscribe(\Magento\Newsletter\Model\Subscriber $subscriber)
    {
        $storeId = $this->getStoreIdFromSubscriber($subscriber);

        if (!$this->_helper->isMailChimpEnabled($storeId)) {
            return;
        }

        if ((int)$subscriber->getSubscriberStatus()
            !== \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED
        ) {
            return;
        }

        $api = $this->_helper->getApi($storeId);

        try {
            $md5HashEmail = hash('md5', strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update(
                $this->_helper->getDefaultList($storeId),
                $md5HashEmail,
                null,
                'unsubscribed'
            );
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }
    }


    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterDelete(
        \Magento\Newsletter\Model\Subscriber $subscriber
    ) {

        $storeId = $this->getStoreIdFromSubscriber($subscriber);
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $api = $this->_helper->getApi($storeId);
            if ($subscriber->isSubscribed()) {
                try {
                    $md5HashEmail = hash('md5', strtolower($subscriber->getSubscriberEmail()));
                    if ($subscriber->getCustomerId()) {
                        $api->lists->members->update(
                            $this->_helper->getDefaultList($storeId),
                            $md5HashEmail,
                            null,
                            'unsubscribed'
                        );
                    } else {
                        $api->lists->members->delete($this->_helper->getDefaultList($storeId), $md5HashEmail);
                    }
                } catch (\Mailchimp_Error $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return null;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @return int
     */
    protected function getStoreIdFromSubscriber(\Magento\Newsletter\Model\Subscriber $subscriber)
    {
        return $subscriber->getStoreId();
    }
}
