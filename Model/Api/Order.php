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
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_orderCollection;
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
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection,
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
        $this->_orderCollection = $orderCollection;
        $this->_date            = $date;
        $this->_apiProduct      = $apiProduct;
        $this->_productRepository   = $productRepository;
        $this->_product         = $product;
        $this->_apiCustomer     = $apiCustomer;
        $this->_countryInformation  = $countryInformation;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_batchId         = \Ebizmarts\MailChimp\Helper\Data::IS_ORDER. '_' . $this->_date->gmtTimestamp();
    }
    public function sendOrders($storeId)
    {
        $batchArray = array();
        $collection = $this->_orderCollection;
        $collection->addFieldToFilter('main_table.store_id',array('eq'=>$storeId))
            ->addFieldToFilter('main_table.state',array('eq'=>\Ebizmarts\MailChimp\Helper\Data::ORDER_STATE_OK));
        $collection->getSelect()->joinLeft(
            ['m4m' => 'mailchimp_sync_ecommerce'],
            "m4m.store_id = main_table.store_id and m4m.related_id = main_table.entity_id",
            []
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_delta is null or (m4m.mailchimp_sync_delta < '".
            $this->_helper->getMCMinSyncDateFlag()."' and m4m.type = '".\Ebizmarts\MailChimp\Helper\Data::IS_ORDER."')");
        $this->_helper->log((string)$collection->getSelect());
        $counter = 0;
        $mailchimpStoreId = $this->_helper->getConfigValue(\Ebizmarts\Mailchimp\Helper\Data::XML_PATH_STORE);
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
                $orderJson = $this->GeneratePOSTPayload($order, $storeId);
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
                $chimpSyncEcommerce = $this->_getChimpSyncEcommerce($order->getStoreId(),$oneOrder->getId(),\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
//                if(!$chimpSyncEcommerce) {
                    $chimpSyncEcommerce->setStoreId($order->getStoreId());
                    $chimpSyncEcommerce->setType(\Ebizmarts\MailChimp\Helper\Data::IS_ORDER);
                    $chimpSyncEcommerce->setRelatedId($oneOrder->getId());
//                }
                $chimpSyncEcommerce->setMailchimpSyncModified(0);
                $chimpSyncEcommerce->setMailchimpSyncDelta($this->_date->gmtDate());
                $chimpSyncEcommerce->setMailchimpSyncError('');
                $chimpSyncEcommerce->getResource()->save($chimpSyncEcommerce);
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

        if (!$itemCount) {
            return "";
        }
        $api = $this->_helper->getApi();
        $customers = array();
        try {
            $customers = $api->ecommerce->customers->getByEmail($mailchimpStoreId, $order->getCustomerEmail());
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }

        if (isset($customers['total_items']) && $customers['total_items'] > 0) {
            $id = $customers['customers'][0]['id'];
            $data['customer'] = array(
                'id' => $id
            );
            $guestCustomer = $this->_apiCustomer->createGuestCustomer($id, $order);
            $mergeFields = $this->_apiCustomer->getMergeVars($guestCustomer);
            if (is_array($mergeFields)) {
                $data['customer'] = array_merge($mergeFields, $data['customer']);
            }
        } else {
            if ((bool)$order->getCustomerIsGuest()) {
                $guestId = "GUEST-" . $this->_helper->getDateMicrotime();
                $data["customer"] = array(
                    "id" => $guestId,
                    "email_address" => $order->getCustomerEmail(),
                    "opt_in_status" => false
                );
                $guestCustomer = $this->_apiCustomer->createGuestCustomer($guestId, $order);
                $mergeFields = $this->_apiCustomer->getMergeVars($guestCustomer);
                if (is_array($mergeFields)) {
                    $data['customer'] = array_merge($mergeFields, $data['customer']);
                }
            } else {
                $data["customer"] = array(
                    "id" => $order->getCustomerId(),
                    "email_address" => $order->getCustomerEmail(),
                    "opt_in_status" => $this->_apiCustomer->getOptin()
                );
            }
            if($order->getCustomerFirstname()) {
                $data["customer"]["first_name"] = $order->getCustomerFirstname();
            }
            if($order->getCustomerLastname()) {
                $data["customer"]["last_name"] = $order->getCustomerLastname();
            }
        }

        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $country =  $this->_countryInformation->getCountryInfo($billingAddress->getCountryId());
        $data["customer"]["address"] = array(
            "address1" => $street[0],
            "address2" => count($street) > 1 ? $street[1] : "",
            "city" => $billingAddress->getCity(),
            "province" => $billingAddress->getRegion() ? $billingAddress->getRegion() : "",
            "province_code" => $billingAddress->getRegionCode() ? $billingAddress->getRegionCode() : "",
            "postal_code" => $billingAddress->getPostcode(),
            "country" => $country->getFullNameLocale(),
            "country_code" => $country->getTwoLetterAbbreviation()
        );
        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $billingAddress->getCompany();
        }
        //customer orders data
        $orderCollection = $this->_orderCollection
            ->addFieldToFilter('state', array('eq' => 'complete'))
            ->addAttributeToFilter('customer_email', array('eq' => $order->getCustomerEmail()))
            ->addFieldToFilter('mailchimp_sync_delta', array('notnull' => true))
            ->addFieldToFilter('mailchimp_sync_delta', array('neq' => ''))
            ->addFieldToFilter('mailchimp_sync_delta', array('gt' => $this->_helper->getMCMinSyncDateFlag()))
            ->addFieldToFilter('mailchimp_sync_error', array('eq' => ""));
        $totalOrders = 1;
        $totalAmountSpent = (int)$order->getGrandTotal();
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

        //enconde to JSON
        try {

            $jsonData = json_encode($data);

        } catch (Exception $e) {
            //json encode failed
            $this->_helper->log("Order ".$order->getId()." json encode failed");
        }
        return $jsonData;
    }
}