<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/20/17 4:48 PM
 * @file: EcommercePromoRules.php
 */

class Mailchimp_EcommercePromoRules extends Mailchimp_Abstract
{
    public function add($storeId, $id, $title, $description, $startsAt, $endsAt, $amount, $type, $target, $enabled, $createdAt, $updatedAt)
    {
        $params = [
            'id'=> $id,
            'title' =>$title,
            'description' =>$description,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'amount' => $amount,
            'type' => $type,
            'target' => $target,
            'enabled' => $enabled,
            'created_at_foreign' => $createdAt,
            'updated_at_foreign' => $updatedAt
        ];
        return $this->master->call("ecommerce/stores/$storeId/promo-rules",$params, Mailchimp::POST);
    }
    public function get($storeId, $id,$fields=null,$excludeFields=null,$count=null)
    {
        $params = array();
        if($count) $params['count'] = $count;
        if($fields) $params['fields'] = $fields;
        if($excludeFields) $params['exclude_fields'] = $excludeFields;
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$id",$params,Mailchimp::GET);
    }
    public function delete($storeId,$id)
    {
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$id",null,Mailchimp::DELETE);
    }
}