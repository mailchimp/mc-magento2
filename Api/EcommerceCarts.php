<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:12 PM
 * @file: EcommerceStoresCarts.php
 */
class Mailchimp_EcommerceCarts extends Mailchimp_Abstract
{
    /**
     * @param $storeId              The store id.
     * @param $id                   A unique identifier for the cart.
     * @param $customer             Information about a specific customer. Carts for existing customers should include
     *                              only the id parameter in the customer object body.
     * @param null $campaignId      A string that uniquely identifies the campaign for a cart.
     * @param null $checkoutUrl     The URL for the cart.
     * @param $currencyCode         The three-letter ISO 4217 code for the currency that the cart uses.
     * @param $orderTotal           The order total for the cart.
     * @param null $taxTotal        The total tax for the cart.
     * @param $lines                An array of the cart’s line items.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($storeId,$id,$customer,$campaignId=null,$checkoutUrl=null,$currencyCode=null,$orderTotal=null,$taxTotal=null,$lines=null)
    {
        $_params = array('id'=>$id,'customer'=>$customer,'currency_code'=>$currencyCode,'order_total'=>$orderTotal,'lines'=>$lines);
        if($campaignId) $_params['campaign_id'] = $campaignId;
        if($checkoutUrl) $_params['checkout_url'] = $checkoutUrl;
        if($taxTotal) $_params['tax_total'] = $taxTotal;
        return $this->master->call('ecommerce/stores/'.$storeId.'/carts',$_params,Mailchimp::POST);
    }

    /**
     * @param $storeId              The store id.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($storeId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('ecommerce/stores/'.$storeId.'/carts',$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $cartId               The id for the cart.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($storeId,$cartId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('ecommerce/stores/'.$storeId.'/carts/'.$cartId,$_params,Mailchimp::GET);
    }
    public function delete($storeId,$cartId)
    {
        return $this->master->call('ecommerce/stores/'.$storeId.'/carts/'.$cartId,null,Mailchimp::DELETE);
    }
}