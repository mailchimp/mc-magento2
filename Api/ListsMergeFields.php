<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 3:50 PM
 * @file: ListsMergeFields.php
 */
class Mailchimp_ListsMergeFields extends Mailchimp_Abstract
{
    /**
     * @param $listId               The unique id for the list.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with
     *                              dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null $count           The number of records to return
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections
     *                              with this parameter can be slow.
     * @param null $type            The merge field type.
     * @param null $required        The boolean value if the merge field is required.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$fields=null,$excludeFields=null,$count=null,$offset=null,$type=null,$required=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        if($type) $_params['type'] = $type;
        if($required) $_params['required'] = $required;
        return $this->master->call('lists/'.$listId.'/merge-fields',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $mergeId              The id for the merge field.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$mergeId)
    {
        return $this->master->call('lists/'.$listId.'/merge-fields/'.$mergeId,null,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param null $tag             The tag used in MailChimp campaigns and for the /members endpoint.
     * @param $name                 The name of the merge field.
     * @param $type                 The type for the merge field.
     * @param null $required        The boolean value if the merge field is required.
     * @param null $defaulValue     The default value for the merge field if null.
     * @param null $public          Whether the merge field is displayed on the signup form.
     * @param $displayOrder         The order that the merge field displays on the list signup form.
     * @param null $options         Extra options for some merge field types.
     * @param null $helpText        Extra text to help the subscriber fill out the form.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($listId,$tag=null,$name=null,$type=null,$required=null,$defaulValue=null,$public=null,$displayOrder=null,
                        $options=null,$helpText=null)
    {
        $_params = array('name'=>$name,'type'=>$type);
        if($tag) $_params['tag'] = $tag;
        if($required) $_params['required'] = $required;
        if($defaulValue) $_params['default_value'] = $defaulValue;
        if($public) $_params['public'] = $public;
        if($displayOrder) $_params['display_order'] = $displayOrder;
        if($options) $_params['options'] = $options;
        if($helpText) $_params['help_text'] = $helpText;
        return $this->master->call('lists/'.$listId.'/merge-fields/',$_params,Mailchimp::POST);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $mergeId              The id for the merge field.
     * @param null $tag             The tag used in MailChimp campaigns and for the /members endpoint.
     * @param null $name            The name of the merge field.
     * @param null $type            The type for the merge field.
     * @param null $required        The boolean value if the merge field is required.
     * @param null $defaulValue     The default value for the merge field if null.
     * @param null $public          Whether the merge field is displayed on the signup form.
     * @param $displayOrder         The order that the merge field displays on the list signup form.
     * @param null $options         Extra options for some merge field types.
     * @param null $helpText        Extra text to help the subscriber fill out the form.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($listId,$mergeId,$tag=null,$name=null,$type=null,$required=null,$defaulValue=null,$public=null,
                           $displayOrder=null,$options=null,$helpText=null)
    {
        $_params = array();
        if($name) $_params['name'] = $name;
        if($type) $_params['type'] = $type;
        if($tag) $_params['tag'] = $tag;
        if($required) $_params['required'] = $required;
        if($defaulValue) $_params['default_value'] = $defaulValue;
        if($public) $_params['public'] = $public;
        if($displayOrder) $_params['display_order'] = $displayOrder;
        if($options) $_params['options'] = $options;
        if($helpText) $_params['help_text'] = $helpText;
        return $this->master->call('lists/'.$listId.'/merge-fields/'.$mergeId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $mergeId              The id for the merge field.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($listId,$mergeId)
    {
        return $this->master->call('lists/'.$listId.'/merge-fields/'.$mergeId,null,Mailchimp::DELETE);
    }
}