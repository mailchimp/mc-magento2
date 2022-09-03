<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:07 PM
 * @file: ListsInterestCategoryInterests.php
 */
class Mailchimp_ListsInterestCategoryInterests extends Mailchimp_Abstract
{
    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$interestCategoryId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('lists/'.$listId.'/interest-categories/'.$interestCategoryId.'/interests',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @param $interestId           The specific interest or ‘group name’.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$interestCategoryId,$interestId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('lists/'.$listId.'/interest-categories/'.$interestCategoryId.'/interests/'.$interestId,$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @param $interestId           The specific interest or ‘group name’.
     * @param null $_listId
     * @param $name                 The name of the interest. This can be shown publicly on a subscription form.
     * @param null $subscriberCount The number of subscribers associated with this interest.
     * @param null $displayOrder    The display order for interests.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($listId,$interestCategoryId,$interestId,$_listId=null,$name=null,$subscriberCount=null,$displayOrder=null)
    {
        $_params = array('name'=>$name);
        if($subscriberCount) $_params['subscriber_count'] = $subscriberCount;
        if($displayOrder) $_params['display_order'] = $displayOrder;
        if($_listId) $_params['list_id'] = $_listId;
        return $this->master->call('lists/'.$listId.'/interest-categories/'.$interestCategoryId.'/interests/'.$interestId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $interestCategoryId   The unique id for the interest category.
     * @param $interestId           The specific interest or ‘group name’.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($listId,$interestCategoryId,$interestId)
    {
        return $this->master->call('lists/'.$listId.'/interest-categories/'.$interestCategoryId.'/interests/'.$interestId,null,Mailchimp::DELETE);
    }
}
