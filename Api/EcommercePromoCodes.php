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
 * @file: EcommercePromoCodes.php
 */

class Mailchimp_EcommercePromoCodes extends Mailchimp_Abstract
{
    public function add($storeId,$ruleId, $id, $code, $redemptionUrl, $usageCount, $enabled, $createdAt, $updatedAt)
    {
        $params = array('code'=>$code,'redemption_url'=>$redemptionUrl);
        if($usageCount) { $params['usage_count'] = $usageCount;}
        if($enabled){ $params['enabled'] = $enabled; }
        if($createdAt) { $params['created_at_foreign'] = $createdAt; }
        if($updatedAt) { $params['updated_at_foreign'] = $updatedAt; }
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$ruleId/promo-codes/$id",$params,Mailchimp::POST);
    }
    public function getAll($storeId, $ruleId, $fields=null,$excludeFields=null,$count=null)
    {
        $params = array();
        if($count) $params['count'] = $count;
        if($fields) $params['fields'] = $fields;
        if($excludeFields) $params['exclude_fields'] = $excludeFields;
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$ruleId/promo-codes",$params,Mailchimp::GET);
    }
    public function get($storeId, $ruleId, $id,$fields=null,$excludeFields=null,$count=null)
    {
        $params = array();
        if($count) $params['count'] = $count;
        if($fields) $params['fields'] = $fields;
        if($excludeFields) $params['exclude_fields'] = $excludeFields;
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$ruleId/promo-codes/$id",$params,Mailchimp::GET);
    }
    public function delete($storeId,$ruleId, $id)
    {
        return $this->master->call("ecommerce/stores/$storeId/promo-rules/$ruleId/promo-codes/$id",null,Mailchimp::DELETE);
    }

}