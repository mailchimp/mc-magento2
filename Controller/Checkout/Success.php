<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/20/17 5:06 PM
 * @file: Success.php
 */

namespace Ebizmarts\MailChimp\Controller\Checkout;

use Ebizmarts\MailChimp\Helper\Sync as SyncHelper;
use Magento\Store\Model\StoreManagerInterface;

class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $_interestGroupFactory;
    /**
     * @var SyncHelper
     */
    private $syncHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param SyncHelper $syncHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        SyncHelper $syncHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->_pageFactory         =$pageFactory;
        $this->_helper              = $helper;
        $this->_checkoutSession     = $checkoutSession;
        $this->_subscriberFactory   = $subscriberFactory;
        $this->_interestGroupFactory= $interestGroupFactory;
        $this->syncHelper           = $syncHelper;
        $this->storeManager         = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $params     = $this->getRequest()->getParams();
        $order = $this->_checkoutSession->getLastRealOrder();
        $websiteId = (int)$this->storeManager->getStore($order->getStoreId())->getWebsiteId();
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         * @var $interestGroup \Ebizmarts\MailChimp\Model\MailChimpInterestGroup
         */
        $subscriber = $this->_subscriberFactory->create();
        $interestGroup = $this->_interestGroupFactory->create();
        try {
            $subscriber->loadBySubscriberEmail($order->getCustomerEmail(), $websiteId);
            if ($subscriber->getEmail()==$order->getCustomerEmail()) {
                if ($subscriber->getStatus()==\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED) {
                    $subscriber->subscribe($subscriber->getEmail());
                }
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(), $subscriber->getStoreId());
                $interestGroup->setGroupdata($this->_helper->serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->_helper->getGmtDate());
                $interestGroup->getResource()->save($interestGroup);
                $listId = $this->_helper->getGeneralList($order->getStoreId());
                $this->_updateSubscriber($listId, $subscriber->getId(), $this->_helper->getGmtDate(), '', 1);
            } else {
                $this->_subscriberFactory->create()->subscribe($order->getCustomerEmail());
                $subscriber->loadBySubscriberEmail($order->getCustomerEmail(), $websiteId);
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(), $subscriber->getStoreId());
                $interestGroup->setGroupdata($this->_helper->serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->_helper->getGmtDate());
                $interestGroup->getResource()->save($interestGroup);
            }
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        $this->messageManager->addSuccessMessage(__('Thanks for sharing your interest with us'));

        return $this->_redirect($this->_helper->getBaserUrl(
            $order->getStoreId(),
            \Magento\Framework\UrlInterface::URL_TYPE_WEB
        ));
    }
    protected function _updateSubscriber(
        $listId,
        $entityId,
        $sync_delta = null,
        $sync_error = null,
        $sync_modified = null
    ) {
        $this->syncHelper->saveEcommerceData(
            $listId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}
