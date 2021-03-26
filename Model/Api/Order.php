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

use Magento\SalesRule\Model\RuleRepository;
use Symfony\Component\Config\Definition\Exception\Exception;

class Order
{
    const BATCH_LIMIT = 50;
    const PAID = 'paid';
    const PARTIALLY_PAID = 'partially_paid';
    const SHIPPED = 'shipped';
    const PARTIALLY_SHIPPED = 'partially_shipped';
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
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $couponRepository;
    /**
     * @var RuleRepository
     */
    protected $ruleRepository;
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
     * @param Product $apiProduct
     * @param Customer $apiCustomer
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce
     * @param \Magento\SalesRule\Model\Coupon $couponRepository
     * @param RuleRepository $ruleRepository
     * @param \Magento\Framework\Url $urlHelper
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\Sales\Model\OrderRepository $order,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Ebizmarts\MailChimp\Model\Api\Product $apiProduct,
        \Ebizmarts\MailChimp\Model\Api\Customer $apiCustomer,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce $chimpSyncEcommerce,
        \Magento\SalesRule\Model\Coupon $couponRepository,
        \Magento\SalesRule\Model\RuleRepository $ruleRepository,
        \Magento\Framework\Url $urlHelper
    ) {
    
        $this->_helper          = $helper;
        $this->_order           = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_apiProduct      = $apiProduct;
        $this->_productFactory   = $productFactory;
        $this->_product         = $product;
        $this->_apiCustomer     = $apiCustomer;
        $this->_countryFactory  = $countryFactory;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_batchId         = \Ebizmarts\MailChimp\Helper\Data::IS_ORDER. '_' . $this->_helper->getGmtTimeStamp();
        $this->_counter = 0;
        $this->_urlHelper    = $urlHelper;
        $this->couponRepository = $couponRepository;
        $this->ruleRepository = $ruleRepository;
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
        $this->_firstDate = $this->_helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_ECOMMERCE_FIRSTDATE, $magentoStoreId);

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
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $magentoStoreId
        );
        $modifiedOrders = $this->_getCollection();
        // select orders for the current Magento store id
        $modifiedOrders->addFieldToFilter('store_id', ['eq' => $magentoStoreId]);
        //join with mailchimp_ecommerce_sync_data table to filter by sync data.
        $modifiedOrders->getSelect()->joinLeft(
            ['m4m' => $this->_helper->getTableName('mailchimp_sync_ecommerce')],
            "m4m.related_id = main_table.entity_id and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        // be sure that the order are already in mailchimp and not deleted
        $modifiedOrders->getSelect()->where(
            "m4m.mailchimp_sync_modified = 1 AND m4m.mailchimp_store_id = '".$mailchimpStoreId."'"
        );
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
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $this->_helper->log($error);
                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                    continue;
                }
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }

                $orderJson = $this->generatePOSTPayload($order, $mailchimpStoreId, $magentoStoreId, true);
                if ($orderJson!==false) {
                    if (!empty($orderJson)) {
                        $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::ORD_MOD);
                        $batchArray[$this->_counter]['method'] = "PATCH";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders/' .
                            $order->getIncrementId();
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                    } else {
                        $error = __('Order ['.$order->getIncrementId().'] is empty');
                        $this->_helper->log($error);
                        $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                        continue;
                    }
                    //update order delta
                    $this->_updateOrder($mailchimpStoreId, $orderId);
                    $this->_counter++;
                } else {
                    $error = __('Json error');
                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                    continue;
                }
            } catch (Exception $e) {
                $this->_helper->log($e->getMessage());
                $error = $e->getMessage();
                $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
            }
        }
        return $batchArray;
    }

    protected function _getNewOrders($magentoStoreId)
    {
        $batchArray = [];
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $magentoStoreId
        );
        $newOrders = $this->_getCollection();
        // select carts for the current Magento store id
        $newOrders->addFieldToFilter('store_id', ['eq' => $magentoStoreId]);
        // filter by first date if exists.
        if ($this->_firstDate) {
            $newOrders->addFieldToFilter('created_at', ['gt' => $this->_firstDate]);
        }
        $newOrders->getSelect()->joinLeft(
            ['m4m' => $this->_helper->getTableName('mailchimp_sync_ecommerce')],
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
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $this->_helper->log($error);
                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                    continue;
                }
                if (count($productData)) {
                    foreach ($productData as $p) {
                        $batchArray[$this->_counter] = $p;
                        $this->_counter++;
                    }
                }
                $orderJson = $this->generatePOSTPayload($order, $mailchimpStoreId, $magentoStoreId);
                if ($orderJson!==false) {
                    if (!empty($orderJson)) {
                        $this->_helper->modifyCounter(\Ebizmarts\MailChimp\Helper\Data::ORD_NEW);
                        $batchArray[$this->_counter]['method'] = "POST";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/orders';
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        //update order delta
                        $this->_updateOrder($mailchimpStoreId, $orderId);
                        $this->_counter++;
                    } else {
                        $error = __('Order ['.$item->getIncrementId().'] is empty');
                        $this->_helper->log($error);
                        $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                    }
                } else {
                    $error = __('Json error');
                    $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
                    continue;
                }
            } catch (Exception $e) {
                $this->_helper->log($e->getMessage());
                $error = $e->getMessage();
                $this->_updateOrder($mailchimpStoreId, $orderId, $this->_helper->getGmtDate(), $error, 0);
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
    protected function generatePOSTPayload(
        \Magento\Sales\Model\Order $order,
        $mailchimpStoreId,
        $magentoStoreId,
        $isModifiedOrder = false
    ) {
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
        $dataPromo = $this->_getPromoData($order);
        if ($dataPromo !== null) {
            $data['promos'] = $dataPromo;
        }
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
                if (!isset($options['simple_sku'])) {
                    $this->_helper->log('The product ['.$item->getId().'] has no simple_sku');
                    continue;
                }
                $sku = $options['simple_sku'];
                $variant = $this->_productFactory->create()->getIdBySku($sku);
                if (!$variant) {
                    continue;
                }
            } else {
                $variant = $item->getProductId();
            }
            if ($productSyncData->getRelatedId() == $item->getProductId() &&
                $productSyncData->getMailchimpSyncError() == '' && $variant) {
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
        $data['customer'] = [
            'id' => hash('md5', strtolower($order->getCustomerEmail())),
            'email_address' => $order->getCustomerEmail(),
            'opt_in_status' => $this->_apiCustomer->getOptin($magentoStoreId)
        ];

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

        if ($order->getCustomerIsGuest()) {
            if ($billingAddress->getFirstname()) {
                $data["customer"]["first_name"] = $billingAddress->getFirstname();
            }
            if ($billingAddress->getLastname()) {
                $data["customer"]["last_name"] = $billingAddress->getLastname();
            }
        }

        $street = $billingAddress->getStreet();
        $address = [];

        if ($street[0]) {
            $address["address1"] = $street[0];
            $data['billing_address']["address1"] = $street[0];
        }

        if (array_key_exists(1, $street)) {
            $address["address2"] = $street[1];
            $data['billing_address']["address2"] = $street[1];
        }
        if (array_key_exists(2, $street)) {
            if (array_key_exists('address2',$address)) {
                $address["address2"] = $address['address2'] . ", " . $street[2];
                $data['billing_address']["address2"] = $data['billing_address']["address2"] . ", " . $street[2];
            } else {
                $address["address2"] = $street[2];
                $data['billing_address']["address2"] = $street[2];
            }
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
            /**
             * @var $country \Magento\Directory\Model\Country
             */
            $country = $this->_countryFactory->create()->loadByCode($billingAddress->getCountryId());
            $address["country"] = $data['billing_address']['country'] = $country->getName();
            $address["country_code"] = $data['billing_address']['country_code'] = $billingAddress->getCountryId();
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
            if ($street[0]) {
                $data['shipping_address']["address1"] = $street[0];
            }

            if (array_key_exists(1, $street)) {
                $data['shipping_address']["address2"] = $street[1];
            }
            if (array_key_exists(2, $street)) {
                if (array_key_exists('address2',$data['shipping_address'])) {
                    $data['shipping_address']["address2"] = $data['shipping_address']["address2"] . ", " . $street[2];
                } else {
                    $data['shipping_address']["address2"] = $street[2];
                }
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
                /**
                 * @var $country \Magento\Directory\Model\Country
                 */
                $country = $this->_countryFactory->create()->loadByCode($shippingAddress->getCountryId());
                $data['shipping_address']["country"] = $country->getName();
                $data['shipping_address']["country_code"] = $shippingAddress->getCountryId();
            }
            if ($shippingAddress->getCompany()) {
                $data["shipping_address"]["company"] = $shippingAddress->getCompany();
            }
        }
        //customer orders data
        $orderCollection = $this->_orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('state', [
            ['neq' => \Magento\Sales\Model\Order::STATE_CANCELED],
            ['neq' => \Magento\Sales\Model\Order::STATE_CLOSED]])
            ->addAttributeToFilter('customer_email', ['eq' => $order->getCustomerEmail()]);

        $orderCollection
            ->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['grand_total', 'total_refunded', 'total_canceled']);

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

        $data["customer"]["orders_count"] = $totalOrders;
        $data["customer"]["total_spent"] = $totalAmountSpent;
        $jsonData = "";

        //enconde to JSON
        $jsonData = json_encode($data);
        if ($jsonData===false) {
            $jsonError = json_last_error();
            $jsonErrorMsg = json_last_error_msg();
            $this->_helper->log('');
            $this->_helper->log("$jsonErrorMsg on order [".$order->getEntityId()."]");
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

    protected function _getPromoData(\Magento\Sales\Model\Order $order)
    {
        $promo = null;
        try {
            $couponCode = $order->getCouponCode();
            if ($couponCode !== null) {
                $code = $this->couponRepository->loadByCode($couponCode);
                if ($code->getCouponId() !== null) {
                    $rule = $this->ruleRepository->getById($code->getRuleId());
                    if ($rule->getRuleId() !== null) {
                        $amountDiscounted = $order->getBaseDiscountAmount();
                        $type = $rule->getSimpleAction();
                        if ($type == 'by_percent') {
                            $type = 'percentage';
                        } else {
                            $type = 'fixed';
                        }

                        $promo = [[
                            'code' => $couponCode,
                            'amount_discounted' => abs($amountDiscounted),
                            'type' => $type
                        ]];
                    }
                }
            }
        } catch(\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $promo;
    }

    protected function _updateOrder($storeId, $entityId, $sync_delta = null, $sync_error = null, $sync_modified = null)
    {
        if (!empty($sync_error)) {
            $sent = \Ebizmarts\MailChimp\Helper\Data::NOTSYNCED;
        } else {
            $sent = \Ebizmarts\MailChimp\Helper\Data::WAITINGSYNC;
        }
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_ORDER,
            $sync_delta,
            $sync_error,
            $sync_modified,
            null,
            null,
            $sent
        );
    }
}
