<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/15/17 11:02 AM
 * @file: Subscriber.php
 */
namespace Ebizmarts\MailChimp\Model\Api;

class Subscriber
{
    const BATCH_LIMIT = 100;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    protected $_subscriberCollection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_message;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * Subscriber constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Newsletter\Model\Subscriber Factory $subscriberFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Message\ManagerInterface $message
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Message\ManagerInterface $message
    )
    {
        $this->_helper                  = $helper;
        $this->_subscriberCollection    = $subscriberCollection;
        $this->_date                    = $date;
        $this->_message                 = $message;
        $this->_subscriberFactory       = $subscriberFactory;
    }

    public function sendSubscribers($storeId, $listId)
    {
        //get subscribers
//        $listId = $this->_helper->getGeneralList($storeId);
        $collection = $this->_subscriberCollection->create();
        $collection->addFieldToFilter('subscriber_status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $storeId));
        $collection->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.related_id = main_table.subscriber_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER.
            "' and m4m.mailchimp_store_id = '".$listId."'",
            ['m4m.*']
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta IS null ".
            "OR (m4m.mailchimp_sync_delta > '".$this->_helper->getMCMinSyncDateFlag().
            "' and m4m.mailchimp_sync_modified = 1)");
        $collection->getSelect()->limit(self::BATCH_LIMIT);
        $subscriberArray = array();
        $date = $this->_helper->getDateMicrotime();
        $batchId = \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER . '_' . $date;
        $counter = 0;
        /**
         * @var $subscriber \Magento\Newsletter\Model\Subscriber
         */
        foreach ($collection as $subscriber) {
            $data = $this->_buildSubscriberData($subscriber);
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $subscriberJson = "";
            //enconde to JSON
            try {
                $subscriberJson = json_encode($data);
            } catch (\Exception $e) {
                //json encode failed
                $errorMessage = "Subscriber ".$subscriber->getSubscriberId()." json encode failed";
                $this->_helper->log($errorMessage, $storeId);
            }
            if (!empty($subscriberJson)) {
                $subscriberArray[$counter]['method'] = "PUT";
                $subscriberArray[$counter]['path'] = "/lists/" . $listId . "/members/" . $md5HashEmail;
                $subscriberArray[$counter]['operation_id'] = $batchId . '_' . $subscriber->getSubscriberId();
                $subscriberArray[$counter]['body'] = $subscriberJson;
                //update subscribers delta
                $this->_updateSubscriber($listId, $subscriber->getId(), $this->_date->gmtDate(), '', 0);
            }
            $counter++;
        }
        return $subscriberArray;
    }

    protected function _buildSubscriberData(\Magento\Newsletter\Model\Subscriber $subscriber)
    {
        $this->_helper->log(__METHOD__);
        $storeId = $subscriber->getStoreId();
        $data = array();
        $data["email_address"] = $subscriber->getSubscriberEmail();
        $mergeVars = $this->getMergeVars($subscriber);
        if ($mergeVars) {
            $data["merge_fields"] = $mergeVars;
        }
        $data["status_if_new"] = $this->_getMCStatus($subscriber->getStatus(), $storeId);
        return $data;
    }
    public function getMergeVars($subscriber)
    {
        return null;
//        $storeId = $subscriber->getStoreId();
//        $mapFields = Mage::helper('mailchimp')->getMapFields($storeId);
//        $maps = unserialize($mapFields);
//        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
//        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
//            ->setEntityTypeFilter(1)
//            ->addSetInfo()
//            ->getData();
//        $mergeVars = array();
//        $subscriberEmail = $subscriber->getSubscriberEmail();
//        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($subscriberEmail);
//        foreach ($maps as $map) {
//            $customAtt = $map['magento'];
//            $chimpTag = $map['mailchimp'];
//            if ($chimpTag && $customAtt) {
//                $eventValue = null;
//                $key = strtoupper($chimpTag);
//                if (is_numeric($customAtt)) {
//                    foreach ($attrSetId as $attribute) {
//                        if ($attribute['attribute_id'] == $customAtt) {
//                            $attributeCode = $attribute['attribute_code'];
//                            switch ($attributeCode) {
//                                case 'email':
//                                    break;
//                                case 'default_billing':
//                                case 'default_shipping':
//                                    $address = $customer->getPrimaryAddress($attributeCode);
//                                    if ($address) {
//                                        $street = $address->getStreet();
//                                        $eventValue = $mergeVars[$key] = array(
//                                            "addr1" => $street[0] ? $street[0] : "",
//                                            "addr2" => count($street) > 1 ? $street[1] : "",
//                                            "city" => $address->getCity() ? $address->getCity() : "",
//                                            "state" => $address->getRegion() ? $address->getRegion() : "",
//                                            "zip" => $address->getPostcode() ? $address->getPostcode() : "",
//                                            "country" => $address->getCountry() ? Mage::getModel('directory/country')->loadByCode($address->getCountry())->getName() : ""
//                                        );
//                                    }
//                                    break;
//                                case 'gender':
//                                    if ($customer->getData($attributeCode)) {
//                                        $genderValue = $customer->getData($attributeCode);
//                                        if ($genderValue == 1) {
//                                            $eventValue = $mergeVars[$key] = 'Male';
//                                        } elseif ($genderValue == 2) {
//                                            $eventValue = $mergeVars[$key] = 'Female';
//                                        }
//                                    }
//                                    break;
//                                case 'group_id':
//                                    if ($customer->getData($attributeCode)) {
//                                        $group_id = (int)$customer->getData($attributeCode);
//                                        $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
//                                        $eventValue = $mergeVars[$key] = $customerGroup[$group_id];
//                                    } else {
//                                        $eventValue = $mergeVars[$key] = __('NOT LOGGED IN');
//                                    }
//                                    break;
//                                case 'firstname':
//                                    $firstName = $customer->getFirstname();
//                                    if (!$firstName) {
//                                        $firstName = $subscriber->getSubscriberFirstname();
//                                    }
//                                    if ($firstName) {
//                                        $eventValue = $mergeVars[$key] = $firstName;
//                                    }
//                                    break;
//                                case 'lastname':
//                                    $lastName = $customer->getLastname();
//                                    if (!$lastName) {
//                                        $lastName = $subscriber->getSubscriberLastname();
//                                    }
//                                    if ($lastName) {
//                                        $eventValue = $mergeVars[$key] = $lastName;
//                                    }
//                                    break;
//                                case 'store_id':
//                                    $eventValue = $mergeVars[$key] = $storeId;
//                                    break;
//                                case 'website_id':
//                                    $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
//                                    $eventValue = $mergeVars[$key] = $websiteId;
//                                    break;
//                                case 'created_in':
//                                    if ($customer->getData($attributeCode)) {
//                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
//                                    } else {
//                                        $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();
//                                        $eventValue = $mergeVars[$key] = $storeCode;
//                                    }
//                                    break;
//                                case 'dob':
//                                    if ($customer->getData($attributeCode)) {
//                                        $eventValue = $mergeVars[$key] = date("m/d", strtotime($customer->getData($attributeCode)));
//                                    }
//                                    break;
//                                default:
//                                    if ($customer->getData($attributeCode)) {
//                                        $eventValue = $mergeVars[$key] = $customer->getData($attributeCode);
//                                    }
//                                    break;
//                            }
//                            Mage::dispatchEvent(
//                                'mailchimp_merge_field_send_before', array(
//                                    'subscriber_email' => $subscriberEmail,
//                                    'merge_field_tag' => $attributeCode,
//                                    'merge_field_value' => &$eventValue
//                                )
//                            );
//                        }
//                    }
//                } else {
//                    switch ($customAtt) {
//                        case 'billing_company':
//                        case 'shipping_company':
//                            $addr = explode('_', $customAtt);
//                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));
//                            if ($address) {
//                                $company = $address->getCompany();
//                                if ($company) {
//                                    $eventValue = $mergeVars[$key] = $company;
//                                }
//                            }
//                            break;
//                        case 'billing_telephone':
//                        case 'shipping_telephone':
//                            $addr = explode('_', $customAtt);
//                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));
//                            if ($address) {
//                                $telephone = $address->getTelephone();
//                                if ($telephone) {
//                                    $eventValue = $mergeVars[$key] = $telephone;
//                                }
//                            }
//                            break;
//                        case 'billing_country':
//                        case 'shipping_country':
//                            $addr = explode('_', $customAtt);
//                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));
//                            if ($address) {
//                                $countryCode = $address->getCountry();
//                                if ($countryCode) {
//                                    $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
//                                    $eventValue = $mergeVars[$key] = $countryName;
//                                }
//                            }
//                            break;
//                        case 'billing_zipcode':
//                        case 'shipping_zipcode':
//                            $addr = explode('_', $customAtt);
//                            $address = $customer->getPrimaryAddress('default_' . ucfirst($addr[0]));
//                            if ($address) {
//                                $zipCode = $address->getPostcode();
//                                if ($zipCode) {
//                                    $eventValue = $mergeVars[$key] = $zipCode;
//                                }
//                            }
//                            break;
//                        case 'dop':
//                            $dop = Mage::helper('mailchimp')->getLastDateOfPurchase($subscriberEmail);
//                            if ($dop) {
//                                $eventValue = $mergeVars[$key] = $dop;
//                            }
//                            break;
//                    }
//                    Mage::dispatchEvent(
//                        'mailchimp_merge_field_send_before', array(
//                            'subscriber_email' => $subscriberEmail,
//                            'merge_field_tag' => $customAtt,
//                            'merge_field_value' => &$eventValue
//                        )
//                    );
//                }
//                if ($eventValue) {
//                    $mergeVars[$key] = $eventValue;
//                }
//            }
//        }
//        return (!empty($mergeVars)) ? $mergeVars : null;
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param bool|false $updateStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateSubscriber(\Magento\Newsletter\Model\Subscriber $subscriber, $updateStatus = false)
    {
        $storeId = $subscriber->getStoreId();
        $listId = $this->_helper->getGeneralList($storeId);
        $newStatus = $this->_getMCStatus($subscriber->getStatus(), $storeId);
        $forceStatus = ($updateStatus) ? $newStatus : null;
        $api = $this->_helper->getApi($storeId);
        $mergeVars = $this->getMergeVars($subscriber);
        $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
        try {
            $api->lists->members->addOrUpdate(
                $listId, $md5HashEmail, $subscriber->getSubscriberEmail(), $newStatus, null, $forceStatus, $mergeVars,
                null, null, null, null
            );
            $this->_updateSubscriber($listId, $subscriber->getId(), $this->_date->gmtDate(), '', 0);
        } catch(\MailChimp_Error $e) {
            if ($newStatus === 'subscribed' && strstr($e->getMessage(), 'is in a compliance state')) {
                try {
                    $api->lists->members->update($listId, $md5HashEmail, null, 'pending', $mergeVars);
                    $subscriber->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED);
                    $message = __('To begin receiving the newsletter, you must first confirm your subscription');
                    $this->_message->addWarningMessage($message);
                } catch(\MailChimp_Error $e) {
                    $this->_helper->log($e->getMessage(), $storeId);
                    $this->_message->addErrorMessage(($e->getMessage()));
                    $subscriber->unsubscribe();
                } catch (\Exception $e) {
                    $this->_helper->log($e->getMessage(), $storeId);
                }
            } else {
                $subscriber->unsubscribe();
                $this->_helper->log($e->getMessage(), $storeId);
                $this->_message->addErrorMessage($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage(), $storeId);
        }
    }
    /**
     * Get status to send confirmation if Need to Confirm enabled on Magento
     *
     * @param $status
     * @param $storeId
     * @return string
     */
    protected function _getMCStatus($status, $storeId)
    {
        $confirmationFlagPath = \Magento\Newsletter\Model\Subscriber::XML_PATH_CONFIRMATION_FLAG;
        if ($status == \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED) {
            $status = 'unsubscribed';
        } elseif ($this->_helper->getConfigValue($confirmationFlagPath, $storeId) &&
            ($status == \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE ||
                $status == \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED)
        ) {
            $status = 'pending';
        } elseif ($status == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
            $status = 'subscribed';
        }
        return $status;
    }
    public function removeSubscriber(\Magento\Newsletter\Model\Subscriber  $subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $listId = $this->_helper->getGeneralList($storeId);
        $api = $this->_helper->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'unsubscribed');
        } catch(\MailChimp_Error $e) {
            $this->_helper->log($e->getMessage(), $storeId);
            $this->_message->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage(), $storeId);
        }
    }
    public function deleteSubscriber(\Magento\Newsletter\Model\Subscriber $subscriber)
    {
        $storeId = $subscriber->getStoreId();
        $listId = $this->_helper->getGeneralList($storeId);
        $api = $this->_helper->getApi($storeId);
        try {
            $md5HashEmail = md5(strtolower($subscriber->getSubscriberEmail()));
            $api->lists->members->update($listId, $md5HashEmail, null, 'cleaned');
        } catch(\MailChimp_Error $e) {
            $this->_helper->log($e->getMessage(), $storeId);
            $this->_message->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage(), $storeId);
        }
    }
    public function update($emailAddress, $storeId)
    {
        $subscriber = $this->_subscriberFactory->create();
        $subscriber->getResource()->loadByEmail($emailAddress);
        if ($subscriber->getStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED &&
            $subscriber->getMailchimpSyncDelta() > $this->_helper->getMCMinSyncDateFlag($storeId)) {
            $listId = $this->_helper->getGeneralList($storeId);
            $this->_updateSubscriber($listId, $subscriber->getId(),$this->_date->gmtDate(),'',1 );
        }
    }
    protected function _updateSubscriber($listId, $entityId, $sync_delta, $sync_error='', $sync_modified=0)
    {
        $this->_helper->saveEcommerceData($listId, $entityId, $sync_delta, $sync_error, $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_SUBSCRIBER);
    }
}