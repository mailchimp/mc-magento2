<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:30 PM
 * @file: EcommerceOrders.php
 */

class Mailchimp_EcommerceOrders extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_EcommerceOrdersLines
     */
    public $lines;

    /**
     * @param $storeId                  The store id.
     * @param $id                       A unique identifier for the order.
     * @param $customer                 Information about a specific customer. This information will update any existing
     *                                  customer. If the customer doesn’t exist in the store, a new customer will be created.
     * @param null $campaignId          A string that uniquely identifies the campaign for an order.
     * @param null $financialStatus     The order status. For example: refunded, processing, cancelled, etc.
     * @param null $fullfillmentStatus  The fulfillment status for the order. For example: partial, fulfilled, etc.
     * @param $currencyCode             The three-letter ISO 4217 code for the currency that the store accepts.
     * @param $orderTotal               The total for the order.
     * @param null $taxTotal            The tax total for the order.
     * @param null $processedAtForeign  The date and time the order was processed.
     * @param null $cancelledAtForeign  The date and time the order was cancelled.
     * @param null $updateAtForeign     The date and time the order was updated.
     * @param null $shippingAddress     The shipping address for the order.
     * @param null $billingAddress      The billing address for the order.
     * @param $lines                    An array of the order’s line items.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($storeId,$id,$customer,$campaignId =null,$financialStatus=null,$fullfillmentStatus=null,$currencyCode=null,
                        $orderTotal=null,$taxTotal=null,$processedAtForeign=null,$cancelledAtForeign=null,$updateAtForeign=null,
                        $shippingAddress=null,$billingAddress=null,$lines=null)
    {
        $_params=array('id'=>$id,'customer'=>$customer,'currency_code'=>$currencyCode,'order_total'=>$orderTotal,'lines'=>$lines);
        if($campaignId) $_params['campaign_id'] = $campaignId;
        if($financialStatus) $_params['financial_status'] = $financialStatus;
        if($fullfillmentStatus) $_params['fullfillment_status'] = $fullfillmentStatus;
        if($taxTotal) $_params['tax_total'] = $taxTotal;
        if($processedAtForeign) $_params['processed_at_foreign'] = $processedAtForeign;
        if($cancelledAtForeign) $_params['cancelled_at_foreign'] = $cancelledAtForeign;
        if($updateAtForeign) $_params['update_at_foreign'] = $updateAtForeign;
        if($shippingAddress) $_params['shipping_address'] = $shippingAddress;
        if($billingAddress) $_params['billing_address'] = $billingAddress;
        return $this->master->call('ecommerce/stores/'.$storeId.'/orders',$_params,Mailchimp::POST);
    }

    /**
     * @param $storeId              The store id.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @param null $customerId      Restrict results to orders made by a specific customer.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($storeId,$fields=null,$excludeFields=null,$count=null,$offset=null,$customerId=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        if($customerId) $_params['customer_id'] = $customerId;
        return $this->master->call('ecommerce/stores/'.$storeId.'/orders',$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $orderId              The id for the order in a store.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($storeId,$orderId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields']= $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('ecommerce/stores/'.$storeId.'/orders/'.$orderId,$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId                  The store id.
     * @param $orderId                  The id for the order in a store.
     * @param $customer                 Information about a specific customer. This information will update any existing
     *                                  customer. If the customer doesn’t exist in the store, a new customer will be created.
     * @param null $campaignId          A string that uniquely identifies the campaign for an order.
     * @param null $financialStatus     The order status. For example: refunded, processing, cancelled, etc.
     * @param null $fullfillmentStatus  The fulfillment status for the order. For example: partial, fulfilled, etc.
     * @param $currencyCode             The three-letter ISO 4217 code for the currency that the store accepts.
     * @param null $taxTotal            The tax total for the order.
     * @param null $processedAtForeign  The date and time the order was processed.
     * @param null $cancelledAtForeign  The date and time the order was cancelled.
     * @param null $updateAtForeign     The date and time the order was updated.
     * @param null $shippingAddress     The shipping address for the order.
     * @param null $billingAddress      The billing address for the order.
     * @param $lines                    An array of the order’s line items.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($storeId,$orderId,$customer=null,$campaignId=null,$financialStatus=null,$fullfillmentStatus=null,
                           $currencyCode=null,$orderTotal=null,$taxTotal=null,$processedAtForeign=null,$cancelledAtForeign=null,
                           $updateAtForeign=null,$shippingAddress=null,$billingAddress=null,$lines=null)
    {
        $_params = array();
        if($customer) $_params['customer'] = $customer;
        if($campaignId) $_params['campaign_id'] = $campaignId;
        if($financialStatus) $_params['financial_status'] = $financialStatus;
        if($fullfillmentStatus) $_params['fullfillment_status'] = $fullfillmentStatus;
        if($currencyCode) $_params['currency_code'] = $currencyCode;
        if($orderTotal)  $_params['order_total'] = $orderTotal;
        if($taxTotal) $_params['tax_total'] = $taxTotal;
        if($processedAtForeign) $_params['processed_at_foreign'] = $processedAtForeign;
        if($cancelledAtForeign) $_params['cancelled_at_foreign'] = $cancelledAtForeign;
        if($updateAtForeign) $_params['update_at_foreign'] = $updateAtForeign;
        if($shippingAddress) $_params['shipping_address'] = $shippingAddress;
        if($billingAddress) $_params['billing_address'] = $billingAddress;
        if($lines) $_params['lines'] = $lines;
        return $this->master->call('ecommerce/stores/'.$storeId.'/orders/'.$orderId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $storeId                  The store id.
     * @param $orderId                  The id for the order in a store.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($storeId,$orderId)
    {
        return $this->master->call('ecommerce/stores/'.$storeId.'/orders/'.$orderId,null,Mailchimp::DELETE);
    }
}