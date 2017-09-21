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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    protected $_address;

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
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Customer\Model\Address $address
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Customer\Model\Address $address,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
    
        $this->_helper              = $helper;
        $this->_collection          = $collection;
        $this->_orderCollection     = $orderCollection;
        $this->_date                = $date;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER. '_' . $this->_date->gmtTimestamp();
        $this->_countryInformation  = $countryInformation;
        $this->_address             = $address;
        $this->_customerFactory     = $customerFactory;
    }
    public function sendCustomers($storeId)
    {
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $storeId);
        $collection = $this->_collection->create();
        $collection->addFieldToFilter('store_id', ['eq'=>$storeId]);
        $collection->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
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

        foreach ($collection as $item) {
            $customer = $this->_customerFactory->create();
            $customer->getResource()->load($customer, $item->getId());


//            $item->getId());
            $data           = $this->_buildCustomerData($customer);
            $customerJson   = '';

            try {
                $customerJson = json_encode($data);
            } catch (Exception $e) {
                $this->_helper->log('Customer: '.$customer->getId().' json encode failed');
            }
            if (!empty($customerJson)) {
                $customerArray[$counter]['method'] = "PUT";
                $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/" . $customer->getId();
                $customerArray[$counter]['operation_id'] = $this->_batchId . '_' . $customer->getId();
                $customerArray[$counter]['body'] = $customerJson;

                //update customers delta
                $this->_updateCustomer($mailchimpStoreId, $customer->getId(), $this->_date->gmtDate(), '', 0);
            }
            $counter++;
        }
        return $customerArray;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    protected function _buildCustomerData(\Magento\Customer\Model\Customer $customer)
    {
        $point = 0;
        $data = [];
        $data["id"] = $customer->getId();
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
                $country = $this->_countryInformation->getCountryInfo($address->getCountryId());
                $countryName = $country->getFullNameLocale();
                $customerAddress["country"] = $countryName;
                $customerAddress["country_code"] = $country->getTwoLetterAbbreviation();
            }
            if (count($customerAddress)) {
                $data["address"] = $customerAddress;
            }
            //company
//                if ($address->getCompany()) {
//                    $data["company"] = $address->getCompany();
//                }
//                break;
//            }
//        }
        }
        return $data;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function getMergeVars(\Magento\Customer\Model\Customer $customer)
    {
        return [];
    }

    /**
     * @param $guestId
     * @param $order
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function createGuestCustomer($guestId, $order)
    {
        $guestCustomer = $this->_customerFactory->create();
        $guestCustomer->setId($guestId);
        foreach ($order->getData() as $key => $value) {
            $keyArray = explode('_', $key);
            if ($value && isset($keyArray[0]) && $keyArray[0] == 'customer') {
                $guestCustomer->{'set' . ucfirst($keyArray[1])}($value);
            }
        }
        return $guestCustomer;
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
    protected function _updateCustomer($storeId, $entityId, $sync_delta, $sync_error, $sync_modified)
    {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            $sync_delta,
            $sync_error,
            $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER
        );
    }
}
