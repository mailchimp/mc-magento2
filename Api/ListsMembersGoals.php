<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:32 PM
 * @file: ListsMembersGoals.php
 */
class Mailchimp_ListsMembersGoals extends Mailchimp_Abstract
{
    /**
     * @param $listId           The unique id for the list.
     * @param $subscriberHash   The MD5 hash of the lowercase version of the list member’s email address.
     * @param $fields           A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param $excludeFields    A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$subscriberHash,$fields,$excludeFields)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash.'/goals',$_params,Mailchimp::GET);
    }
}