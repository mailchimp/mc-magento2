<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/27/17 8:14 PM
 * @file: Save.php
 */

namespace Ebizmarts\MailChimp\Model\Plugin\Newsletter;

class Save
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory
     */
    protected $interestGroupFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * Save constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Ebizmarts\MailChimp\Model\MailChimpInterestGroupFactory $interestGroupFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->helper               = $helper;
        $this->customerSession      = $customerSession;
        $this->subscriberFactory    = $subscriberFactory;
        $this->request              = $request;
        $this->interestGroupFactory = $interestGroupFactory;
        $this->date                 = $date;
    }
    public function afterExecute()
    {
        $params = $this->request->getParams();

        $subscriber = $this->subscriberFactory->create();
        $interestGroup = $this->interestGroupFactory->create();
        /**
         * @var $customer \Magento\Customer\Model\Customer
         */
        $customer = $this->customerSession->getCustomer();

        $email = $customer->getEmail();

        try {
            $subscriber->loadByCustomerId($this->customerSession->getCustomerId());
            if($subscriber->getEmail()==$email) {
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(),$subscriber->getStoreId());
                $interestGroup->setGroupdata(serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->date->gmtDate());
                $interestGroup->getResource()->save($interestGroup);
                $listId = $this->helper->getGeneralList($subscriber->getStoreId());
                $this->_updateSubscriber($listId, $subscriber->getId(), $this->date->gmtDate(), '', 1);
            } else {
                $this->subscriberFactory->create()->subscribe($email);
                $subscriber->loadByEmail($email);
                $interestGroup->getBySubscriberIdStoreId($subscriber->getSubscriberId(),$subscriber->getStoreId());
                $interestGroup->setGroupdata(serialize($params));
                $interestGroup->setSubscriberId($subscriber->getSubscriberId());
                $interestGroup->setStoreId($subscriber->getStoreId());
                $interestGroup->setUpdatedAt($this->date->gmtDate());
                $interestGroup->getResource()->save($interestGroup);
            }

        } catch(\Exception $e) {
        }
    }
    protected function _updateSubscriber($listId, $entityId, $sync_delta, $sync_error='', $sync_modified=0)
    {
        $this->helper->saveEcommerceData($listId, $entityId, $sync_delta, $sync_error, $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER);
    }
}