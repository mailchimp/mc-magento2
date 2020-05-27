<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/1/16 12:00 PM
 * @file: Customer.php
 */

namespace Ebizmarts\MailChimp\Model\Api;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Exception\State\ExpiredException;
use Symfony\Component\Config\Definition\Exception\Exception;

class Customer
{
    const MAX           = 100;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_collection;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollection;
    /**
     * @var CountryFactory
     */
    protected $_countryFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $_address;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var string
     */
    protected $_batchId;

    /**
     * Customer constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param CountryFactory $countryFactory
     * @param \Magento\Customer\Model\Address $address
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Model\Address $address,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
    
        $this->_helper              = $helper;
        $this->_collection          = $collection;
        $this->_orderCollection     = $orderCollection;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER. '_' .
            $this->_helper->getGmtTimeStamp();
        $this->_address             = $address;
        $this->_customerFactory     = $customerFactory;
        $this->_countryFactory      = $countryFactory;
        $this->subscriberFactory    = $subscriberFactory;
    }
    public function sendCustomers($storeId)
    {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $storeId
        );
        $listId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_PATH_LIST, $storeId);
        $collection = $this->_collection->create();
        $collection->addFieldToFilter('store_id', ['eq'=>$storeId]);
        $collection->getSelect()->joinLeft(
            ['m4m' => $this->_helper->getTableName('mailchimp_sync_ecommerce')],
            "m4m.related_id = e.entity_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null ".
            "OR (m4m.mailchimp_sync_delta > '".$this->_helper->getMCMinSyncDateFlag().
            "' and m4m.mailchimp_sync_modified = 1)");
        $collection->getSelect()->limit(self::MAX);
        $counter = 0;
        $customerArray = [];
        $this->_helper->resetMapFields();

        foreach ($collection as $item) {
            $customer = $this->_customerFactory->create();
            $customer->getResource()->load($customer, $item->getId());
            $data           = $this->_buildCustomerData($customer);
            $customerJson   = '';

            $customerJson = json_encode($data);
            if ($customerJson!==false) {
                if (!empty($customerJson)) {
                    if ($item->getMailchimpSyncModified() == 1) {
                        $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::CUS_MOD);
                    } else {
                        $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::CUS_NEW);
                    }
                    $customerMailchimpId = hash('md5', strtolower($customer->getEmail()));
                    $customerArray[$counter]['method'] = "PUT";
                    $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/" .
                        $customerMailchimpId;
                    $customerArray[$counter]['operation_id'] = $this->_batchId . '_' . $customer->getId();
                    $customerArray[$counter]['body'] = $customerJson;
                    $counter++;
                    if (!$this->isSubscriber($customer)) {
                        $subscriberData = $this->buildSubscriberData($customer);
                        $subscriberJson = json_encode($subscriberData);
                        if ($subscriberJson !==false) {
                            $customerArray[$counter]['method'] = "PATCH";
                            $customerArray[$counter]['path'] = "/lists/" . $listId . "/members/" .
                                $customerMailchimpId;
                            $customerArray[$counter]['operation_id'] = $this->_batchId . '_' .
                                $customer->getId().'_SUB';
                            $customerArray[$counter]['body'] = $subscriberJson;
                            $counter++;
                        }
                    }
                    //update customers delta
                    $this->_updateCustomer($mailchimpStoreId, $customer->getId());
                } else {
                    $this->_updateCustomer(
                        $mailchimpStoreId,
                        $customer->getId(),
                        $this->_helper->getGmtDate(),
                        'Customer with no data',
                        0
                    );
                }
            } else {
                $this->_updateCustomer(
                    $mailchimpStoreId,
                    $customer->getId(),
                    $this->_helper->getGmtDate(),
                    json_last_error_msg(),
                    0
                );
            }
        }
        return $customerArray;
    }
    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return mixed
     */
    protected function buildSubscriberData(\Magento\Customer\Model\Customer $customer)
    {
        $data = [];
        $data["merge_fields"] = $this->_helper->getMergeVars($customer, $customer->getData('store_id'));
        return $data;
    }
    protected function isSubscriber(\Magento\Customer\Model\Customer $customer)
    {
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByEmail($customer->getEmail());
        if ($subscriber->getEmail() == $customer->getEmail()) {
            if ($subscriber->getStatus() === \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    protected function _buildCustomerData(\Magento\Customer\Model\Customer $customer)
    {
        $point = 0;
        $data = [];
        $data["id"] = hash('md5', strtolower($customer->getEmail()));
        $data["email_address"] = $customer->getEmail() ? $customer->getEmail() : '';
        $data["first_name"] = $customer->getFirstname() ? $customer->getFirstname() : '';
        $data["last_name"] = $customer->getLastname() ? $customer->getLastname() : '';
        $data["opt_in_status"] = $this->getOptin();
        // customer order data
        $orderCollection = $this->_orderCollection->create();
        $orderCollection->addFieldToFilter('state', [
            ['neq',\Magento\Sales\Model\Order::STATE_CANCELED],
            ['neq',\Magento\Sales\Model\Order::STATE_CLOSED]])
            ->addAttributeToFilter('customer_id', ['eq' => $customer->getId()]);
        $totalOrders = 0;
        $totalAmountSpent = 0;
        /**
         * @var $customerOrder \Magento\Sales\Model\Order
         */
        foreach ($orderCollection as $customerOrder) {
            $totalOrders++;
            $totalAmountSpent += $customerOrder->getGrandTotal() - $customerOrder->getTotalRefunded()
                - $customerOrder->getTotalCanceled();
        }
        $data['orders_count']   = $totalOrders;
        $data['total_spent']    = $totalAmountSpent;
        $address = $customer->getDefaultBillingAddress();
        if ($address) {
            $customerAddress = [];
            if ($street = $address->getStreet()) {
                $street = $address->getStreet();
                if ($street[0]) {
                    $customerAddress["address1"] = $street[0];
                }
                if (count($street) > 1) {
                    $customerAddress["address2"] = $street[1];
                }
            }
            if ($address->getCity()) {
                $customerAddress["city"] = $address->getCity();
            }
            if ($address->getRegion()) {
                $customerAddress["province"] = $address->getRegion();
            }
            if ($address->getRegionCode()) {
                $customerAddress["province_code"] = $address->getRegionCode();
            }
            if ($address->getPostcode()) {
                $customerAddress["postal_code"] = $address->getPostcode();
            }
            if ($address->getCountryId()) {
                /**
                 * @var $country \Magento\Directory\Model\Country
                 */
                $country = $this->_countryFactory->create()->loadByCode($address->getCountryId());
                $customerAddress["country"] = $country->getName();
                $customerAddress["country_code"] = $address->getCountryId();
            }
            if (count($customerAddress)) {
                $data["address"] = $customerAddress;
            }
        }
        return $data;
    }

    public function getOptin($storeId = 0)
    {
        if ($this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_ECOMMERCE_OPTIN, $storeId)) {
            $optin = true;
        } else {
            $optin = false;
        }
        return $optin;
    }
    protected function _updateCustomer(
        $storeId,
        $entityId,
        $sync_delta = null,
        $sync_error = null,
        $sync_modified = null
    ) {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}
