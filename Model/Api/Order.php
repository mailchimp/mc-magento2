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

    protected $_api = null;

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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Url
     */
    protected $_urlHelper;
    protected $_chimpSyncEcommerce;
    protected $_firstDate;
    protected $_counter;

    protected $_batchId;

    /**
     * Order constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderRepository $order
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param Product $apiProduct
     * @param Customer $apiCustomer
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Sales\Model\OrderRepository $order,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Ebizmarts\MailChimp\Model\Api\Product $apiProduct,
        \Ebizmarts\MailChimp\Model\Api\Customer $apiCustomer,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformation,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce,
        \Magento\Framework\Url $urlHelper
    ) {
    
        $this->_helper          = $helper;
        $this->_order           = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_date            = $date;
        $this->_apiProduct      = $apiProduct;
        $this->_productFactory   = $productFactory;
        $this->_product         = $product;
        $this->_apiCustomer     = $apiCustomer;
        $this->_countryInformation  = $countryInformation;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_batchId         = \Ebizmarts\MailChimp\Helper\Data::IS_ORDER. '_' . $this->_date->gmtTimestamp();
        $this->_firstDate = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_ECOMMERCE_FIRSTDATE);
        $this->_counter = 0;
        $this->_urlHelper    = $urlHelper;
    }

    /**
     * Set the request for orders to be created on MailChimp
     *
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function sendOrders($magentoStoreId)
    {
        $batchArray = [];

        // get all the orders modified
        $batchArray = array_merge($batchArray, $this->_getModifiedOrders($magentoStoreId));
        // get new orders
        $batchArray = array_merge($batchArray, $this->_getNewOrders($magentoStoreId));
        return $batchArray;
    }
    protected function _getCollection()
    {
        return $this->_orderCollectionFactory->create();
    }
    protected function _getModifiedOrders($magentoStoreId)
    {
        $batchArray = [];
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $magentoStoreId);
        $modifiedOrders = $this->_getCollection();
        // select orders for the current Magento store id
        $modifiedOrders->addFieldToFilter('store_id', ['eq' => $magentoStoreId]);
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedOrders->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.related_id = main_table.entity_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        // be sure that the order are already in mailchimp and not deleted
        $modifiedOrders->getSelect()->where("m4m.mailchimp_sync_modified = 1 AND m4m.mailchimp_store_id = '".$mailchimpStoreId."'");
        // limit the collection
        $modifiedOrders->getSelect()->limit(self::BATCH_LIMIT);
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($modifiedOrders as $item) {
            try {
                $error = '';
                $orderId = $item->getEntityId();
                $order = $this->_order->get($orderId);
                //create missing products first
                try {
                    $productData = $this->_apiProduct->sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId);
                } catch(\Exception $e) {
                    $this->_helper->log($e->getMessage());
                    continue;
                }
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }

                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId, true);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "PATCH";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' . $orderId;
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                    $batchArray[$this->_counter]['body'] = $orderJson;
                } else {
                    $error = __('Something went wrong when retreiving product information.');
                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_date->gmtDate(), $error, 0);
                    continue;
                }

                //update order delta
                $this->_updateOrder($mailchimpStoreId, $orderId, $this->_date->gmtDate(), $error, 0);
                $this->_counter++;
            } catch (Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }

        return $batchArray;
    }

    protected function _getNewOrders($magentoStoreId)
    {
        $batchArray = [];
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE, $magentoStoreId);
        $newOrders = $this->_getCollection();
        // select carts for the current Magento store id
        $newOrders->addFieldToFilter('store_id', ['eq' => $magentoStoreId]);
        // filter by first date if exists.
        if ($this->_firstDate) {
            $newOrders->addFieldToFilter('created_at', ['gt' => $this->_firstDate]);
        }
        $newOrders->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.related_id = main_table.entity_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        // be sure that the quote are not in mailchimp
        $newOrders->getSelect()->where("m4m.mailchimp_sync_delta IS NULL");
        // limit the collection
        $newOrders->getSelect()->limit(self::BATCH_LIMIT);

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($newOrders as $item) {
            try {
                $error = '';
                $orderId = $item->getEntityId();
                $order = $this->_order->get($orderId);
                //create missing products first
                try {
                    $productData = $this->_apiProduct->sendModifiedProduct($order, $mailchimpStoreId, $magentoStoreId);
                } catch(\Exception $e) {
                    $this->_helper->log($e->getMessage());
                    continue;
                }
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }
                $orderJson = $this->GeneratePOSTPayload($order, $mailchimpStoreId, $magentoStoreId);
                if (!empty($orderJson)) {
                    $batchArray[$this->_counter]['method'] = "POST";
                    $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                    $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                    $batchArray[$this->_counter]['body'] = $orderJson;
                } else {
                    $error = __('Something went wrong when retreiving product information.');
//                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_date->gmtDate(), $error, 0);
                }

                //update order delta
                $this->_updateOrder($mailchimpStoreId, $orderId, $this->_date->gmtDate(), $error, 0);
                $this->_counter++;
            } catch (Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }

        return $batchArray;
    }

    /**
     * Set all the data for each order to be sent
     *
     * @param $order
     * @param $mailchimpStoreId
     * @param $magentoStoreId
     * @param $isModifiedOrder
     * @return string
     */
    protected function GeneratePOSTPayload(\Magento\Sales\Model\Order $order, $mailchimpStoreId, $magentoStoreId, $isModifiedOrder = false)
    {
        $data = [];
        $data['id'] = $order->getIncrementId();
        if ($order->getMailchimpCampaignId()) {
            $data['campaign_id'] = $order->getMailchimpCampaignId();
        }

        if ($order->getMailchimpLandingPage()) {
            $data['landing_site'] = $order->getMailchimpLandingPage();
        }
        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $order->getTaxAmount();
        $data['discount_total'] = abs($order->getDiscountAmount());
        $data['shipping_total'] = $order->getShippingAmount();
        $statusArray = $this->_getMailChimpStatus($order);
        if (isset($statusArray['financial_status'])) {
            $data['financial_status'] = $statusArray['financial_status'];
        }

        if (isset($statusArray['fulfillment_status'])) {
            $data['fulfillment_status'] = $statusArray['fulfillment_status'];
        }

        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['updated_at_foreign'] = $order->getUpdatedAt();
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_CANCELED) {
            $orderCancelDate = null;
            $commentCollection = $order->getStatusHistoryCollection();
            /**
             * @var $comment \Magento\Sales\Model\Order\Status\History
             */
            foreach ($commentCollection as $comment) {
                if ($comment->getStatus() === \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $orderCancelDate = $comment->getCreatedAt();
                }
            }

            if ($orderCancelDate) {
                $data['cancelled_at_foreign'] = $orderCancelDate;
            }
        }

        $data['lines'] = [];

        //order lines
        $items = $order->getAllVisibleItems();
        $itemCount = 0;
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($items as $item) {
            $variant = null;
            $productSyncData = $this->_helper->getChimpSyncEcommerce(
                $mailchimpStoreId,
                $item->getProductId(),
                \Ebizmarts\MailChimp\Helper\Data::IS_PRODUCT
            );
            if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = $this->_productFactory->create()->getIdBySku($sku);
                if (!$variant) {
                    continue;
                }
            } else {
                $variant = $item->getProductId();
            }
            if ($productSyncData->getMailchimpSyncDelta() && $productSyncData->getMailchimpSyncError() == '' && $variant) {
                $itemCount++;
                $data["lines"][] = [
                    "id" => (string)$itemCount,
                    "product_id" => $item->getProductId(),
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                    "discount" => abs($item->getDiscountAmount())
                ];
            }
        }
        if (!$itemCount) {
            unset($data['lines']);
            return "";
        }

        //customer data
        $api = $this->_helper->getApi();
        $customers = [];
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (\Mailchimp_Error $e) {
            $this->_helper->log($e->getMessage());
        }

        if (!$isModifiedOrder) {
            if (isset($customers['total_items']) && $customers['total_items'] > 0) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = [
                    'id' => $id
                ];
            } else {
                if ((bool)$order->getCustomerIsGuest()) {
                    $guestId = "GUEST-" . $this->_helper->getDateMicrotime();
                    $data["customer"] = [
                        "id" => $guestId,
                        "email_address" => $order->getCustomerEmail(),
                        "opt_in_status" => false
                    ];
                } else {
                    $custEmailAddr = null;
                    try {
                        $customer = $api->ecommerce->customers->get($mailchimpStoreId, $order->getCustomerId(), 'email_address');
                        if (isset($customer['email_address'])) {
                            $custEmailAddr = $customer['email_address'];
                        }
                    } catch (\Mailchimp_Error $e) {
                        $this->_helper->log('no customer found');
                    }

                    $data["customer"] = [
                        "id" => ($order->getCustomerId()) ? $order->getCustomerId() : $guestId = "CUSTOMER-" . $this->_helper->getDateMicrotime(),
                        "email_address" => ($custEmailAddr) ? $custEmailAddr : $order->getCustomerEmail(),
                        "opt_in_status" => $this->_apiCustomer->getOptin($magentoStoreId)
                    ];
                }
            }
        } else {
            if (isset($customers['customers'][0]['id'])) {
                $id = $customers['customers'][0]['id'];
                $data['customer'] = [
                    'id' => $id
                ];
            }
        }
//        $store = Mage::getModel('core/store')->load($magentoStoreId);

        $data['order_url'] = $this->_urlHelper->getUrl(
            'sales/order/view/',
            [
                'order_id' => $order->getId(),
                '_nosid' => true,
                '_secure' => true
            ]
        );
        if ($order->getCustomerFirstname()) {
            $data["customer"]["first_name"] = $order->getCustomerFirstname();
        }

        if ($order->getCustomerLastname()) {
            $data["customer"]["last_name"] = $order->getCustomerLastname();
        }
        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $address = [];

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

        if ($billingAddress->getCountryId()) {
            $country = $this->_countryInformation->getCountryInfo($billingAddress->getCountryId());
            $countryName = $country->getFullNameLocale();
            $address["country"] =$data['billing_address']['country'] = $countryName;
            $address["country_code"] = $data['billing_address']['country_code'] = $country->getTwoLetterAbbreviation();
        }
        if (count($address)) {
            $data["customer"]["address"] = $address;
        }

        if ($billingAddress->getName()) {
            $data['billing_address']['name'] = $billingAddress->getName();
        }

        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $billingAddress->getCompany();
            $data["billing_address"]["company"] = $billingAddress->getCompany();
        }
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
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

            if ($shippingAddress->getCountryId()) {
                $country = $this->_countryInformation->getCountryInfo($shippingAddress->getCountryId());
                $countryName = $country->getFullNameLocale();
                $data['shipping_address']['country'] = $countryName;
                $data['shipping_address']['country_code'] = $country->getTwoLetterAbbreviation();
            }

//            if ($shippingAddress->getCompamy()) {
//                $data["shipping_address"]["company"] = $shippingAddress->getCompany();
//            }
        }
        //customer orders data
        $orderCollection = $this->_orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('state', [
            ['neq',\Magento\Sales\Model\Order::STATE_CANCELED],
            ['neq',\Magento\Sales\Model\Order::STATE_CLOSED]])
            ->addAttributeToFilter('customer_email', ['eq' => $order->getCustomerEmail()]);

        $totalOrders = 1;
        $totalAmountSpent = (int)$order->getGrandTotal();
        /**
         * @var $customerOrder \Magento\Sales\Model\Order
         */
        foreach ($orderCollection as $customerOrder) {
            $totalOrders++;
            $totalAmountSpent += $customerOrder->getGrandTotal() - $customerOrder->getTotalRefunded()
                - $customerOrder->getTotalCanceled();
        }

        $data["customer"]["orders_count"] = $totalOrders;
        $data["customer"]["total_spent"] = $totalAmountSpent;
        $jsonData = "";

        //enconde to JSON
        try {
            $jsonData = json_encode($data);
        } catch (Exception $e) {
            //json encode failed
            $this->_helper->log("Order " . $order->getEntityId() . " json encode failed");
        }

        return $jsonData;
    }

    protected function _getMailChimpStatus(\Magento\Sales\Model\Order $order)
    {
        $mailChimpFinancialStatus = null;
        $mailChimpFulfillmentStatus = null;
        $totalItemsOrdered = $order->getData('total_qty_ordered');
        $shippedItemAmount = 0;
        $invoicedItemAmount = 0;
        $refundedItemAmount = 0;
        $mailChimpStatus = [];
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($order->getAllVisibleItems() as $item) {
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

//    public function update($order, $magentoStoreId)
//    {
//        if (Mage::helper('mailchimp')->isEcomSyncDataEnabled('stores', $magentoStoreId)) {
//            $mailchimpStoreId = Mage::helper('mailchimp')->getMCStoreId('stores', $magentoStoreId);
//            $orderSyncData = Mage::helper('mailchimp')->getEcommerceSyncDataItem($order->getId(), Ebizmarts_MailChimp_Model_Config::IS_ORDER, $mailchimpStoreId);
//            if ($orderSyncData->getMailchimpSyncDelta() > Mage::helper('mailchimp')->getMCMinSyncDateFlag('stores', $magentoStoreId)) {
//                $orderSyncData->setData("mailchimp_sync_error", "")
//                    ->setData("mailchimp_sync_modified", 1)
//                    ->save();
//            }
//        }
//    }

//    /**
//     * Get Api Object
//     *
//     * @param $magentoStoreId
//     * @return Ebizmarts_Mailchimp|null
//     */
//    protected function _getApi($magentoStoreId)
//    {
//        if (!$this->_api) {
//            $this->_api = Mage::helper('mailchimp')->getApi('stores', $magentoStoreId);
//        }
//
//        return $this->_api;
//    }

    protected function _updateOrder($storeId, $entityId, $sync_delta, $sync_error, $sync_modified)
    {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            $sync_delta,
            $sync_error,
            $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER
        );
    }
}
