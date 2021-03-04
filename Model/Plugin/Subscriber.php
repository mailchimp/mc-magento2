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
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_ACTIVE, $storeId)) {
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

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param $email
     * @param $websiteId
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function afterLoadBySubscriberEmail(\Magento\Newsletter\Model\Subscriber $subscriber, $email, $websiteId)
    {
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

        return $subscriber;
    }
}
