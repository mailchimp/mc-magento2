<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 11/21/16 3:51 PM
 * @file: Order.php
 */

namespace Ebizmarts\MailChimp\Model\Api;

use Symfony\Component\Config\Definition\Exception\Exception;

class Order
{
    const BATCH_LIMIT = 50;
    const PAID = 'paid';
    const PARTIALLY_PAID = 'parially_paid';
    const SHIPPED = 'shipped';
    const PARTIALLY_SHIPPED = 'parially_shipped';
    const PENDING = 'pending';
    const REFUNDED = 'refunded';
    const PARTIALLY_REFUNDED = 'partially_refunded';
    const CANCELED = 'canceled';
    const COMPLETE = 'complete';
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var Product
     */
    protected $_apiProduct;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Customer
     */
    protected $_apiCustomer;
    /**
     * @var \Magento\Directory\Api\CountryInformationAcquirerInterface
     */
    protected $_countryInformation;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;
    protected $_chimpSyncEcommerce;
    protected $_firstDate;
    protected $_counter;

    protected $_batchId;

    /**
     * Order constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderRepository $order
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param Product $apiProduct
     * @param Customer $apiCustomer
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     */

    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Sales\Model\OrderRepository $order,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Model\Api\Product  $apiProduct,
        \Ebizmarts\MailChimp\Model\Api\Customer $apiCustomer,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
    )
    {
        $this->_helper          = $helper;
        $this->_order           = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_date            = $date;
        $this->_apiProduct      = $apiProduct;
        $this->_productRepository   = $productRepository;
        $this->_product         = $product;
        $this->_apiCustomer     = $apiCustomer;
        $this->_countryInformation  = $countryInformation;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_batchId         = \Ebizmarts\MailChimp\Helper\Data::IS_ORDER. '_' . $this->_date->gmtTimestamp();
        $this->_firstDate = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_ECOMMERCE_FIRSTDATE);
        $this->_counter = 0;
    }
    public function sendOrders($storeId) {
        $batchArray = array();
        //$mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE);

        // get all the carts modified but not converted in orders
        $batchArray = array_merge($batchArray, $this->_getModifiedOrders($storeId));
        // get new carts
        $batchArray = array_merge($batchArray, $this->_getNewOrders($storeId));

        return $batchArray;
    }
    protected function _getCollection()
    {
        return $this->_orderCollectionFactory->create();
    }
    protected function _getModifiedOrders($storeId)
    {
        $this->_helper->log(__METHOD__);
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE,$storeId);
        $batchArray = array();
        $collection = $this->_getCollection();
        //create missing products first
        $collection->addAttributeToSelect('entity_id');
        $collection->addFieldToFilter('main_table.store_id',array('eq'=>$storeId));
        $collection->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.store_id = main_table.store_id and m4m.related_id = main_table.entity_id",
            []
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta > '".$this->_helper->getMCMinSyncDateFlag().
            "' and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER."' and m4m.mailchimp_sync_modified = 1");
        if($this->_firstDate) {
            $collection->addFieldToFilter('created_at',array('from' => $this->_firstDate));
        }
        $collection->getSelect()->limit(self::BATCH_LIMIT);
        $this->_helper->log((string)$collection->getSelect());
        /**
         * @var $oneOrder \Magento\Sales\Model\Order
         */

        foreach ($collection as $item) {
            try {
                $error = '';
                $oneOrder = $this->_order->get($item->getEntityId());
                $productData = $this->_apiProduct->sendModifiedProduct($oneOrder, $mailchimpStoreId);
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }
                $orderJson = $this->GeneratePOSTPayload($oneOrder, $mailchimpStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "PATCH";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $oneOrder->getEntityId();
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $oneOrder->getEntityId();
                    $batchArray[$this->_counter]['body'] = $orderJson;

                } else {
                    $error = $this->_helper->__('Something went wrong when retreiving product information.');
                }
                //update order delta
                $chimpSyncEcommerce = $this->_getChimpSyncEcommerce($oneOrder->getStoreId(),$oneOrder->getId(),\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
                $chimpSyncEcommerce->setStoreId($oneOrder->getStoreId());
                $chimpSyncEcommerce->setType(\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
                $chimpSyncEcommerce->setRelatedId($oneOrder->getId());
                $chimpSyncEcommerce->setMailchimpSyncModified(0);
                $chimpSyncEcommerce->setMailchimpSyncDelta($this->_date->gmtDate());
                $chimpSyncEcommerce->setMailchimpSyncError($error);
                $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);

                $this->_counter++;
            } catch (Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return $batchArray;
    }

    private function _getNewOrders($storeId)
    {
        $this->_helper->log(__METHOD__);

        $batchArray = array();
        $collection = $this->_getCollection();
        $this->_helper->log((string)$collection->getSelect());
        $this->_helper->log("step 1");
        $collection->addFieldToFilter('main_table.store_id',array('eq'=>$storeId));
//            ->addFieldToFilter('main_table.state',array('eq'=>\Ebizmarts\MailChimp\Helper\Data::ORDER_STATE_OK));
        $this->_helper->log((string)$collection->getSelect());
        $this->_helper->log("step 2");
        $collection->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.store_id = main_table.store_id and m4m.related_id = main_table.entity_id",
            []
        );
        $this->_helper->log((string)$collection->getSelect());
        $this->_helper->log("step 3");
        $collection->getSelect()->where("m4m.mailchimp_sync_delta is null or (m4m.mailchimp_sync_delta < '".
            $this->_helper->getMCMinSyncDateFlag()."' and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER."')");
        $this->_helper->log((string)$collection->getSelect());
        $this->_helper->log("step 4");
        $counter = 0;
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE,$storeId);
        /**
         * @var $oneOrder \Magento\Sales\Model\Order
         */
        foreach ($collection as $oneOrder)
        {
            try {
                /**
                 * @var $order \Magento\Sales\Model\Order
                 */
                $order = $this->_order->get($oneOrder->getId());

                $order->getResource()->save($order);
                $productData = $this->_apiProduct->sendModifiedProduct($order,$mailchimpStoreId);
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$counter] = $p;
                        $counter++;
                    }
                }
                $this->_helper->log("before GeneratePOSTPayload");
                $orderJson = $this->GeneratePOSTPayload($order, $storeId);
                $this->_helper->log("after GeneratePOSTPayload");
                $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE,$storeId);
                if (!empty($orderJson)) {
                    $batchArray[$counter]['method'] = "POST";
                    $batchArray[$counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $order->getEntityId();
                    $batchArray[$counter]['body'] = $orderJson;

                } else {
                    $error = $this->_helper->__('Something went wrong when retreiving product information.');
                    $this->_helper->log($error);
                    $order->setData("mailchimp_sync_error", $error);
                }
                //update order delta
                $this->_helper->log("before save ecommerce");
                $chimpSyncEcommerce = $this->_getChimpSyncEcommerce($order->getStoreId(),$oneOrder->getId(),\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
                $chimpSyncEcommerce->setStoreId($order->getStoreId());
                $chimpSyncEcommerce->setType(\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
                $chimpSyncEcommerce->setRelatedId($oneOrder->getId());
                $chimpSyncEcommerce->setMailchimpSyncModified(0);
                $chimpSyncEcommerce->setMailchimpSyncDelta($this->_date->gmtDate());
                $chimpSyncEcommerce->setMailchimpSyncError('');
                $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);
                $this->_helper->log("after save ecommerce");
                $counter++;
            }
            catch (Exception $e)
            {
                $this->_helper->log('Order '.$oneOrder->getId().' fails '.$e->getMessage());
            }
        }
        return $batchArray;
    }
    private function _getChimpSyncEcommerce($storeId,$id,$type)
    {
        $chimp = $this->_chimpSyncEcommerce->getByStoreIdType($storeId,$id,$type);
        return $chimp;
    }
    /**
     * Return true if order has been already sent to MailChimp and has been modified afterwards.
     *
     * @param $order
     * @return bool
     */
    protected function _isModifiedOrder($order)
    {
        return ($order->getMailchimpSyncModified() && $order->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag());
    }

    protected function _getMailChimpStatus(\Magento\Sales\Model\Order $order)
    {
        $mailChimpFinancialStatus = null;
        $mailChimpFulfillmentStatus = null;
        $totalItemsOrdered = $order->getData('total_qty_ordered');
        $shippedItemAmount = 0;
        $invoicedItemAmount = 0;
        $refundedItemAmount = 0;
        $mailChimpStatus = array();
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */

        foreach ($order->getAllVisibleItems() as $item){
            $shippedItemAmount += $item->getQtyShipped();
            $invoicedItemAmount += $item->getQtyInvoiced();
            $refundedItemAmount += $item->getQtyRefunded();
        }
        if ($shippedItemAmount > 0) {
            if ($totalItemsOrdered > $shippedItemAmount) {
                $mailChimpFulfillmentStatus = self::PARTIALLY_SHIPPED;
            } else {
                $mailChimpFulfillmentStatus = self::SHIPPED;
            }
        }
        if ($refundedItemAmount > 0) {
            if ($totalItemsOrdered > $refundedItemAmount) {
                $mailChimpFinancialStatus = self::PARTIALLY_REFUNDED;
            } else {
                $mailChimpFinancialStatus = self::REFUNDED;
            }
        }
        if ($invoicedItemAmount > 0) {
            if ($refundedItemAmount == 0 || $refundedItemAmount != $invoicedItemAmount) {
                if ($totalItemsOrdered > $invoicedItemAmount) {
                    $mailChimpFinancialStatus = self::PARTIALLY_PAID;
                } else {
                    $mailChimpFinancialStatus = self::PAID;
                }
            }

        }

        if (!$mailChimpFinancialStatus && $order->getState() == \Magento\Sales\Model\Order::STATE_CANCELED) {
            $mailChimpFinancialStatus = self::CANCELED;
        }

        if (!$mailChimpFinancialStatus) {
            $mailChimpFinancialStatus = self::PENDING;
        }

        if ($mailChimpFinancialStatus) {
            $mailChimpStatus['financial_status'] = $mailChimpFinancialStatus;
        }
        if ($mailChimpFulfillmentStatus) {
            $mailChimpStatus['fulfillment_status'] = $mailChimpFulfillmentStatus;
        }
        return $mailChimpStatus;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $storeId
     * @return array
     */
    protected function GeneratePOSTPayload(\Magento\Sales\Model\Order $order,$storeId)
    {
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE,$storeId);
        $data = array();
        $data['id'] = $order->getEntityId();
        if ($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }
        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $order->getTaxAmount();
        $data['shipping_total'] = $order->getShippingAmount();
        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['lines'] = array();

        //order lines
        $this->_helper->log("before items");
        $items = $order->getAllVisibleItems();
        $itemCount = 0;
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($items as $item) {

            if ($item->getProductType()==\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = $this->_productRepository->get($sku);
            } else {
                $sku = $item->getSku();
                $variant = $item->getProductId();
            }

            // load the product and check if the product was already sent to mailchimp
            $this->_product = $this->_productRepository->get($sku);
            $syncDelta = $this->_product->getMailchimpSyncDelta();
            $syncError = $this->_product->getMailchimpSyncError();
            if ($syncDelta&&!$syncError) {
                $itemCount++;
                $data["lines"][] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $item->getProductId(),
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                );
            }
        }
        $this->_helper->log("after items");

        if (!$itemCount) {
            return "";
        }
        $api = $this->_helper->getApi();
        $this->_helper->log("before get customer from mailchimp");
        $customers = array();
        try {
            $this->_helper->log($order->getCustomerEmail());
            $this->_helper->log($mailchimpStoreId);
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getMessage());
        }

        if (!$this->_isModifiedOrder($order)) {
            if (isset($customers['total_items']) && $customers['total_items'] > 0) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = array(
                    'id' => $id
                );
            } else {
                if ((bool)$order->getCustomerIsGuest()) {
                    $guestId = "GUEST-" . $this->_helper->getDateMicrotime();
                    $data["customer"] = array(
                        "id" => $guestId,
                        "email_address" => $order->getCustomerEmail(),
                        "opt_in_status" => false
                    );
                } else {
                    $custEmailAddr = null;
                    try {
                        $customer = $api->ecommerce->customers->get($mailchimpStoreId, $order->getCustomerId(), 'email_address');
                        if (isset($customer['email_address'])) {
                            $custEmailAddr = $customer['email_address'];
                        }
                    } catch (\Mailchimp_Error $e) {
                    }
                    $data["customer"] = array(
                        "id" => ($order->getCustomerId()) ? $order->getCustomerId() : $guestId = "CUSTOMER-" . $this->_helper->getDateMicrotime(),
                        "email_address" => ($custEmailAddr) ? $custEmailAddr : $order->getCustomerEmail(),
                        "opt_in_status" => $this->_apiCustomer->getOptin()
                    );
                }
            }
        } else {
            if (isset($customers['customers'][0]['id'])) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = array(
                    'id' => $id
                );
            }
        }


//        $this->_helper->log("after get customer from mailchimp");
//        $this->_helper->log($customers);
//        if (isset($customers['total_items']) && $customers['total_items'] > 0) {
//            $id = $customers['customers'][0]['id'];
//            $data['customer'] = array(
//                'id' => $id
//            );
//            $guestCustomer = $this->_apiCustomer->createGuestCustomer($id, $order);
//            $mergeFields = $this->_apiCustomer->getMergeVars($guestCustomer);
//            if (is_array($mergeFields)) {
//                $data['customer'] = array_merge($mergeFields, $data['customer']);
//            }
//        } else {
//            if ((bool)$order->getCustomerIsGuest()) {
//                $guestId = "GUEST-" . $this->_helper->getDateMicrotime();
//                $data["customer"] = array(
//                    "id" => $guestId,
//                    "email_address" => $order->getCustomerEmail(),
//                    "opt_in_status" => false
//                );
//                $this->_helper->log('before createGuestCustomer');
//                $guestCustomer = $this->_apiCustomer->createGuestCustomer($guestId, $order);
//                $this->_helper->log('after createGuestCustomer before getMergeVars');
//                $mergeFields = $this->_apiCustomer->getMergeVars($guestCustomer);
//                $this->_helper->log('after getMergeVars');
//                if (is_array($mergeFields)) {
//                    $data['customer'] = array_merge($mergeFields, $data['customer']);
//                }
//            } else {
//                $data["customer"] = array(
//                    "id" => $order->getCustomerId(),
//                    "email_address" => $order->getCustomerEmail(),
//                    "opt_in_status" => $this->_apiCustomer->getOptin()
//                );
//            }
//            if($order->getCustomerFirstname()) {
//                $data["customer"]["first_name"] = $order->getCustomerFirstname();
//            }
//            if($order->getCustomerLastname()) {
//                $data["customer"]["last_name"] = $order->getCustomerLastname();
//            }
//        }
//        $this->_helper->log('after customer, before billing');

        if($order->getCustomerFirstname()) {
            $data["customer"]["first_name"] = $order->getCustomerFirstname();
        }
        if($order->getCustomerLastname()) {
            $data["customer"]["last_name"] = $order->getCustomerLastname();
        }
        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $address = array();

        if ($street[0]) {
            $address["address1"] = $street[0];
            $data['billing_address']["address1"] = $street[0];
        }

        if (count($street) > 1) {
            $address["address2"] = $street[1];
            $data['billing_address']["address2"] = $street[1];
        }

        if ($billingAddress->getCity()) {
            $address["city"] = $billingAddress->getCity();
            $data['billing_address']["city"] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address["province"] = $billingAddress->getRegion();
            $data['billing_address']["province"] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address["province_code"] = $billingAddress->getRegionCode();
            $data['billing_address']["province_code"] = $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address["postal_code"] = $billingAddress->getPostcode();
            $data['billing_address']["postal_code"] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $address["country"] = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
            $address["country_code"] = $billingAddress->getCountry();
            $data['billing_address']["country"] = Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
            $data['billing_address']["country_code"] = $billingAddress->getCountry();
        }

        if (count($address)) {
            $data["customer"]["address"] = $address;
        }
        if ($billingAddress->getName()) {
            $data['billing_address']['name'] = $billingAddress->getName();
        }

        $shippingAddress = $order->getShippingAddress();
        $street = $shippingAddress->getStreet();
        if ($shippingAddress->getName()) {
            $data['shipping_address']['name'] = $shippingAddress->getName();
        }
        if (isset($street[0]) && $street[0]) {
            $data['shipping_address']['address1'] = $street[0];
        }
        if (isset($street[1]) && $street[1]) {
            $data['shipping_address']['address2'] = $street[1];
        }
        if ($shippingAddress->getCity()) {
            $data['shipping_address']['city'] = $shippingAddress->getCity();
        }
        if ($shippingAddress->getRegion()) {
            $data['shipping_address']['province'] = $shippingAddress->getRegion();
        }
        if ($shippingAddress->getRegionCode()) {
            $data['shipping_address']['province_code'] = $shippingAddress->getRegionCode();
        }
        if ($shippingAddress->getPostcode()) {
            $data['shipping_address']['postal_code'] = $shippingAddress->getPostcode();
        }
        if ($shippingAddress->getCountry()) {
            $data['shipping_address']['country'] = Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountry())->getName();
            $data['shipping_address']['country_code'] = $shippingAddress->getCountry();
        }

        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $billingAddress->getCompany();
            $data["billing_address"]["company"] = $billingAddress->getCompany();
        }
        if ($shippingAddress->getCompamy()) {
            $data["shipping_address"]["company"] = $billingAddress->getCompany();
        }
        //customer orders data
        $orderCollection = $this->_getCollection()
            ->addFieldToFilter('state', array('eq' => 'complete'))
            ->addAttributeToFilter('customer_email', array('eq' => $order->getCustomerEmail()));
        if($this->_firstDate) {
            $orderCollection->addFieldToFilter('created_at', array('from' => $this->_firstDate));
        }

        $totalOrders = 1;
        $totalAmountSpent = (int)$order->getGrandTotal();
        $this->_helper->log('after collection, before foreach');
        /**
         * @var $orderAlreadySent \Magento\Sales\Model\Order
         */
        foreach ($orderCollection as $orderAlreadySent) {
            $totalOrders++;
            $totalAmountSpent += (int)$orderAlreadySent->getGrandTotal();
        }
        $data["customer"]["orders_count"] = $totalOrders;
        $data["customer"]["total_spent"] = $totalAmountSpent;
        $jsonData = "";
        $this->_helper->log('after foreach, before json');
        //enconde to JSON
        try {

            $jsonData = json_encode($data);

        } catch (Exception $e) {
            //json encode failed
            $this->_helper->log("Order ".$order->getId()." json encode failed");
        }
        $this->_helper->log('end');
        return $jsonData;
    }
}