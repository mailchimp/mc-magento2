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
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {

            $subscriber = $this->_subscriberFactory->create()->loadByCustomerId($customerId);
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
                } catch (\Mailchimp_Error $e) {
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
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {

            $subscriber = $this->_subscriberFactory->create()->loadByCustomerId($customerId);
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
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
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
                } catch (\Mailchimp_Error $e) {
                    $this->_helper->log($e->getFriendlyMessage());
                }
            }
        }
        return [$email, $storeId];
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeUnsubscribe(
        \Magento\Newsletter\Model\SubscriptionManager $subscriptionManager,
        $email,
        $storeId,
        $confirmCode
    ) {
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
            $api = $this->_helper->getApi($storeId);
            try {
                $md5HashEmail = hash('md5', strtolower($email));
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
        return [$email,$storeId,$confirmCode];
    }
 }