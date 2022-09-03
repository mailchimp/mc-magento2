<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:36 PM
 * @file: EcommerceProductsVariants.php
 */
class Mailchimp_EcommerceProductsVariants extends Mailchimp_Abstract
{
    /**
     * @param $storeId                  The store id.
     * @param $productId                The id for the product of a store.
     * @param $id                       A unique identifier for the product variant.
     * @param $title                    The title of a product variant.
     * @param null $url                 The URL for a product variant.
     * @param null $sku                 The stock keeping unit (SKU) of a product variant.
     * @param null $price               The price of a product variant.
     * @param null $inventoryQuantity   The inventory quantity of a product variant.
     * @param null $imageUrl            The image URL for a product variant.
     * @param null $backorders          The backorders of a product variant.
     * @param null $visibility          The visibility of a product variant.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($storeId,$productId,$id,$title,$url=null,$sku=null,$price=null,$inventoryQuantity=null,
                        $imageUrl=null,$backorders=null,$visibility=null)
    {
        $_params=array('id'=>$id,'title'=>$title);
        if($url) $_params['url'] = $url;
        if($sku) $_params['sku'] = $sku;
        if($price) $_params['price'] = $price;
        if($inventoryQuantity) $_params['inventory_quantity'] = $inventoryQuantity;
        if($imageUrl) $_params['image_url'] = $imageUrl;
        if($backorders) $_params['backorders'] = $backorders;
        if($visibility) $_params['visibility'] = $visibility;
        $this->master->call('/ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants',$_params,Mailchimp::POST);
    }

    /**
     * @param $storeId              The store id.
     * @param $productId            The id for the product of a store.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections
     *                              with this parameter can be slow.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($storeId,$productId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants',$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $productId            The id for the product of a store.
     * @param $variantId            The id for the product variant.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($storeId,$productId,$variantId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants/'.$variantId,$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId                  The store id.
     * @param $productId                The id for the product of a store.
     * @param $variantId                The id for the product variant.
     * @param null $title               The title of a product variant.
     * @param null $url                 The URL for a product variant.
     * @param null $sku                 The stock keeping unit (SKU) of a product variant.
     * @param null $price               The price of a product variant.
     * @param null $inventoryQuantity   The inventory quantity of a product variant.
     * @param null $imageUrl            The image URL for a product variant.
     * @param null $backorders          The backorders of a product variant.
     * @param null $visibility          The visibility of a product variant.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($storeId,$productId,$variantId,$title=null,$url=null,$sku=null,$price=null,
                           $inventoryQuantity=null,$imageUrl=null,$backorders=null,$visibility=null)
    {
        $_params=array();
        if($title) $_params['title'] = $title;
        if($url) $_params['url'] = $url;
        if($sku) $_params['sku'] = $sku;
        if($price) $_params['price'] = $price;
        if($inventoryQuantity) $_params['inventory_quantity'] = $inventoryQuantity;
        if($imageUrl) $_params['image_url'] = $imageUrl;
        if($backorders) $_params['backorders'] = $backorders;
        if($visibility) $_params['visibility'] = $visibility;
        $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants/'.$variantId,$_params,Ebizmarts_Mailchimp::PATCH);
    }

    /**
     * @param $storeId                  The store id.
     * @param $productId                The id for the product of a store.
     * @param $variantId                The id for the product variant.
     * @param $id                       A unique identifier for the product variant.
     * @param $title                    The title of a product variant.
     * @param null $url                 The URL for a product variant.
     * @param null $sku                 The stock keeping unit (SKU) of a product variant.
     * @param null $price               The price of a product variant.
     * @param null $inventoryQuantity   The inventory quantity of a product variant.
     * @param null $imageUrl            The image URL for a product variant.
     * @param null $backorders          The backorders of a product variant.
     * @param null $visibility          The visibility of a product variant.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function addOrModify($storeId,$productId,$variantId,$id,$title,$url=null,$sku=null,$price=null,
                                $inventoryQuantity=null,$imageUrl=null,$backorders=null,$visibility=null)
    {
        $_params=array('id'=>$id,'title'=>$title);
        if($url) $_params['url'] = $url;
        if($sku) $_params['sku'] = $sku;
        if($price) $_params['price'] = $price;
        if($inventoryQuantity) $_params['inventory_quantity'] = $inventoryQuantity;
        if($imageUrl) $_params['image_url'] = $imageUrl;
        if($backorders) $_params['backorders'] = $backorders;
        if($visibility) $_params['visibility'] = $visibility;
        $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants/'.$variantId,$_params,Ebizmarts_Mailchimp::PUT);
    }
    /**
     * @param $storeId                  The store id.
     * @param $productId                The id for the product of a store.
     * @param $variantId                The id for the product variant.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($storeId,$productId,$variantId)
    {
        $this->master->call('ecommerce/stores/'.$storeId.'/products/'.$productId.'/variants/'.$variantId,null,Mailchimp::DELETE);
    }
}