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

use Magento\Newsletter\Model\SubscriberFactory;
use \Ebizmarts\MailChimp\Helper\Data as Helper;
use \Magento\Customer\Model\ResourceModel\CustomerRepository;
use \Magento\Customer\Model\Session;
use \Magento\Store\Model\StoreManagerInterface;

class SubscriptionManager
{
    /**
     * @var Helper
     */
    protected $_helper;
    /**
     * @var CustomerRepository
     */
    protected $_customer;
    /**
     * @var Session
     */
    protected $_customerSession;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    protected $_subscriberFactory;
    protected $_api = null;

    /**
     * SubscriptionManager constructor.
     * @param Helper $helper
     * @param CustomerRepository $customer
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Helper $helper,
        CustomerRepository $customer,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        SubscriberFactory $subscriberFactory
    ) {

        $this->_helper          = $helper;
        $this->_customer        = $customer;
        $this->_customerSession = $customerSession;
        $this->_storeManager    = $storeManager;
        $this->_subscriberFactory = $subscriberFactory;
    }
    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeUnsubscribeCustomer(
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        $customerId,
        $storeId
    ) {
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $websiteId = (int)$this->_storeManager->getStore($storeId)->getWebsiteId();
            $subscriber = $this->_subscriberFactory->create()->loadByCustomer($customerId, $websiteId);
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
        return [$customerId,$storeId];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSubscribeCustomer(
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        $customerId,
        $storeId
    ) {
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $websiteId = (int)$this->_storeManager->getStore($storeId)->getWebsiteId();
            $subscriber = $this->_subscriberFactory->create()->loadByCustomer($customerId, $websiteId);
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
                    } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                        $this->_helper->log($e->getFriendlyMessage());
                    }
                }
            }
        }
        return [$customerId, $storeId];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $email
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSubscribe(
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        $email,
        $storeId
    ) {
        if ($this->_helper->isMailChimpEnabled($storeId)) {
            $websiteId = (int)$this->_storeManager->getStore($storeId)->getWebsiteId();

            $subscriber = $this->_subscriberFactory->create()->loadBySubscriberEmail($email, $websiteId);

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
                    $api->lists->members->addOrUpdate(
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
                } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return [$email, $storeId];
    }

    /**
     * Mirror an unsubscribe to Mailchimp, but only once Magento has made one.
     *
     * This ran `before` the platform's method, which meant it acted on an
     * operation that had not happened yet and sometimes never would.
     * `SubscriptionManager::unsubscribe()` returns early when no subscriber row
     * exists for the address, and `Subscriber::unsubscribe()` raises on a
     * mismatched confirmation code. In both cases nothing changes in Magento,
     * while this plugin had already told Mailchimp the shopper was
     * unsubscribed — and when the address is not a member either, that write is
     * a PATCH answered 404 and swallowed, once per attempt.
     *
     * Reading the returned subscriber is what makes the two sides agree. It
     * carries a row id only if there was a row, and the unsubscribed status
     * only if the transition went through; a refused confirmation code raises,
     * so this method is simply never reached. The sibling customer path checks
     * `isSubscribed()` up front, which covers the missing row but not the code,
     * and could not cover the code without restating the platform's own rule
     * here.
     *
     * @param  \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager
     * @param  \Magento\Newsletter\Model\Subscriber $result
     * @param  string $email
     * @param  int    $storeId
     * @param  string $confirmCode
     * @return \Magento\Newsletter\Model\Subscriber
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterUnsubscribe(
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        \Magento\Newsletter\Model\Subscriber $result,
        $email,
        $storeId,
        $confirmCode
    ) {
        if (!$this->_helper->isMailChimpEnabled($storeId)) {
            return $result;
        }

        if (!$result->getId()
            || (int)$result->getStatus() !== \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED
        ) {
            return $result;
        }

        $api = $this->_helper->getApi($storeId);
        try {
            $md5HashEmail = hash('md5', strtolower($email));
            $api->lists->members->update(
                $this->_helper->getDefaultList($storeId),
                $md5HashEmail,
                null,
                'unsubscribed'
            );
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }

        return $result;
    }
 }
