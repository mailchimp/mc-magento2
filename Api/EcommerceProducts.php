<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:34 PM
 * @file: EcommerceProducts.php
 */
class Mailchimp_EcommerceProducts extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_EcommerceProductsVariants
     */
    public $variants;

    /**
     * @param $storeId                      The store id.
     * @param $id                           A unique identifier for the product.
     * @param $title                        The title of a product.
     * @param null $handle                  The handle of a product.
     * @param null $url                     The URL for a product.
     * @param null $description             The description of a product.
     * @param null $type                    The type of product.
     * @param null $vendor                  The vendor for a product.
     * @param null $imageUrl                The image URL for a product.
     * @param $variants                     An array of the product’s variants. At least one variant is required for
     *                                      each product.
     *                                      A variant can use the same id and title as the parent product.
     * @param null $publishedAtForeign      The date and time the product was published.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($storeId,$id,$title,$handle=null,$url=null,$description=null,$type=null,$vendor=null,
                        $imageUrl=null,$variants=null,$publishedAtForeign=null)
    {
        $_params=array('id'=>$id,'title'=>$title,'variants'=>$variants);
        if($handle) $_params['handle'] = $handle;
        if($url) $_params['url'] = $url;
        if($description) $_params['description'] = $description;
        if($type)  $_params['type'] = $type;
        if($vendor) $_params['vendor'] = $vendor;
        if($imageUrl) $_params['image_url'] = $imageUrl;
        if($publishedAtForeign) $_params['published_at_foreign'] = $publishedAtForeign;
        return $this->master->call('ecommerce/stores/'.$storeId.'/products',$_params,Mailchimp::POST);
    }

    /**
     * @param $storeId              The store id.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections
     *                              with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($storeId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('ecommerce/stores/'.$storeId.'/products',$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $productId            The id for the product of a store.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with
     *                              dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($storeId,$productId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId,$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $productId            The id for the product of a store.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($storeId,$productId)
    {
        return $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId,null,Mailchimp::DELETE);
    }
}