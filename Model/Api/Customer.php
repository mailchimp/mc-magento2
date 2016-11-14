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
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected $_collection;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
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
    protected $_address;

    /**
     * @var string
     */
    protected $_batchId;

    /**
     * Customer constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $collection,
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Magento\Customer\Model\Address $address,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->_helper              = $helper;
        $this->_customerRepository  = $customerRepository;
        $this->_collection          = $collection;
        $this->_orderCollection     = $orderCollection;
        $this->_date                = $date;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_CUSTOMER. '_' . $this->_date->gmtTimestamp();
        $this->_countryInformation  = $countryInformation;
        $this->_address             = $address;
    }
    public function sendCustomers($storeId)
    {
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE);
        $collection = $this->_collection;
        $collection->addAttributeToFilter(
                array(
                    array('attribute' => 'mailchimp_sync_delta', 'null' => true),
                    array('attribute' => 'mailchimp_sync_delta', 'eq' => ''),
                    array('attribute' => 'mailchimp_sync_delta', 'lt' => $this->_helper->getMCMinSyncDateFlag()),
                    array('attribute' => 'mailchimp_sync_modified', 'eq'=> 1)
                ), '', 'left'
            )
            ->addAttributeToFilter('store_id',array('eq'=>$storeId));
        $counter = 0;
        $customerArray = array();

        foreach($collection as $item)
        {
            $customer       = $this->_customerRepository->getById($item->getId());
            $data           = $this->_buildCustomerData($customer);
            $customerJson   = '';

            try {
                $this->_helper->log('before json');
                $customerJson = json_encode($data);
            } catch(Exception $e) {
                $this->_helper->log('Customer: '.$customer->getId().' json encode failed');
            }
            if (!empty($customerJson)) {
                $customerArray[$counter]['method'] = "PUT";
                $customerArray[$counter]['path'] = "/ecommerce/stores/" . $mailchimpStoreId . "/customers/" . $customer->getId();
                $customerArray[$counter]['operation_id'] = $this->_batchId . '_' . $customer->getId();
                $customerArray[$counter]['body'] = $customerJson;

                //update customers delta
                $customer->setCustomAttribute("mailchimp_sync_delta",$this->_date->gmtDate());
                //$customer->setData("mailchimp_sync_delta",$this->_date->gmtDate());
                $customer->setCustomAttribute("mailchimp_sync_error", "");
                $customer->setCustomAttribute("mailchimp_sync_modified", 0);
                $this->_customerRepository->save($customer);
//                $customer->save($customer);

            }
            $counter++;
        }
        return $customerArray;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    protected function _buildCustomerData(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $point = 0;
        $this->_helper->log(__METHOD__);
        $data = array();
        $data['id']             = $customer->getId();
        $data['email_address']  = $customer->getEmail();
        $data['first_name']     = $customer->getFirstName();
        $data['last_name']      = $customer->getLastName();
        $data['opt_in_status']  = false; //$customer->getOptin();
        // customer order data
        $orderCollection = $this->_orderCollection;
        $orderCollection->addFieldToFilter('state', 'complete')
            ->addAttributeToFilter('customer_id', array('eq' => $customer->getId()));
        $totalOrders = 0;
        $totalAmountSpent = 0;
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach($orderCollection as $order) {
            $totalOrders++;
            $totalAmountSpent += $order->getGrandTotal();
        }
        $data['orders_count']   = $totalOrders;
        $data['total_spent']    = $totalAmountSpent;
        foreach($customer->getAddresses() as $address) {
            /**
             * @var $address \Magento\Customer\Model\Address
             */
            if (!array_key_exists('address',$data)) {
                $street = $address->getStreet();
                $country = $this->_countryInformation->getCountryInfo($address->getCountryId());
//                $this->_helper->log('country name'.$point++);
                $countryName = $country->getFullNameLocale();
//                $this->_helper->log($countryName);
//                $this->_helper->log('street '.$point++);
//                $this->_helper->log($street[0]);
//                $this->_helper->log('country '.$point++);
//                $this->_helper->log($country->getTwoLetterAbbreviation());
//                $this->_helper->log('city '.$point++);
//                $this->_helper->log($address->getCity());
//                $this->_helper->log('region '.$point++);
//                //$regionModel = $address->getRegionModel($address->getRegionId());
//                $this->_helper->log($address->getRegion());
//                $this->_helper->log('region code '.$point++);
//                $this->_helper->log($address->getRegionCode());
//                $this->_helper->log('postcode '.$point++);
//                $this->_helper->log($address->getPostcode());
                $data['address'] = array(
                    "address1" => $street[0] ? $street[0] : "",
                    "address2" => count($street)>1 ? $street[1] : "",
                    "city" => $address->getCity() ? $address->getCity() : "",
//                    "province" => $address->getRegion() ? $address->getRegion() : "",
//                    "province_code" => $address->getRegionCode() ? $address->getRegionCode() : "",
                    "postal_code" => $address->getPostcode(),
                    "country" => $countryName,
                    "country_code" => $country->getTwoLetterAbbreviation()
                );
//                $this->_helper->log('before getcompany');
//                if ($address->getCompany()) {
//                    $data["company"] = $address->getCompany();
//                }
                break;
            }
        }
//        $this->_helper->log('antes del mergeVar');
//        $mergeFields = $this->getMergeVars($customer);
//        if (is_array($mergeFields)) {
//            $data = array_merge($mergeFields, $data);
//        }
//        $this->_helper->log('nos vamos');
//        $this->_helper->log(var_export($data,true));
        return $data;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function getMergeVars(\Magento\Customer\Model\Customer $customer)
    {
        return array();
    }
}