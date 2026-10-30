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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected $_api = null;

    /**
     * Subscriber constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {

        $this->_helper          = $helper;
        $this->_storeManager    = $storeManager;
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
                } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
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

    /**
     * Mirror an unsubscribe to Mailchimp, whatever else the platform does next.
     *
     * This hooks the model rather than `SubscriptionManager`, because the model
     * is what every path ends at. The emailed unsubscribe link
     * (`Newsletter\Controller\Subscriber\Unsubscribe`) and the admin grid's
     * mass action both call `Subscriber::unsubscribe()` directly, and
     * `SubscriptionManager::unsubscribe()` delegates to it -- so one hook here
     * covers all three, and a second hook on `SubscriptionManager` would only
     * write to Mailchimp twice for the same unsubscribe.
     *
     * `around` rather than `after`, and the mirror sits in a `finally`. The
     * platform saves the row and then sends its email, so a mail transport that
     * raises leaves Magento unsubscribed and would leave an `after` plugin
     * unreached -- the shopper is out on this side and still subscribed on
     * Mailchimp's, which is the one outcome worth engineering against here.
     * `finally` runs either way and the exception still propagates untouched.
     *
     * Nothing is assumed about whether anything happened: the status is read
     * after the fact. A mismatched confirmation code raises before the row is
     * touched, so the status is still subscribed and nothing is mirrored.
     *
     * @param  \Magento\Newsletter\Model\Subscriber $subscriber
     * @param  callable $proceed
     * @return mixed
     */
    public function aroundUnsubscribe(
        \Magento\Newsletter\Model\Subscriber $subscriber,
        callable $proceed
    ) {
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
        } catch (\Mailchimp_Error | \Mailchimp_HttpError $e) {
            $this->_helper->log($e->getFriendlyMessage());
        }
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $email
     * @param $websiteId
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function afterLoadBySubscriberEmail(\Magento\Newsletter\Model\Subscriber $subscriber, $email, $websiteId)
    {
        if ($this->_helper->isMailChimpEnabled($subscriber->getStoreId())) {
            try {
                if (!$this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL,
                    $subscriber->getStoreId()
                )) {
                    $subscriber->setImportMode(true);
                }
            } catch (\Exception $exception) {
                $this->_helper->log($exception->getMessage());
            }
        }

        return $subscriber;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $customerId
     * @param $websiteId
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function afterLoadByCustomer(\Magento\Newsletter\Model\Subscriber $subscriber, $customerId, $websiteId)
    {
        if ($this->_helper->isMailChimpEnabled($subscriber->getStoreId())) {
            try {
                if (!$this->_helper->getConfigValue(
                    \Ebizmarts\MailChimp\Helper\Data::XML_MAGENTO_MAIL,
                    $subscriber->getStoreId()
                )) {
                    $subscriber->setImportMode(true);
                }
            } catch (\Exception $exception) {
                $this->_helper->log($exception->getMessage());
            }
        }

        return $subscriber;
    }
}
