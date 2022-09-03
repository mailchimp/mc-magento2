<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:33 PM
 * @file: ListsMembersNotes.php
 */
class Mailchimp_ListsMembersNotes extends Mailchimp_Abstract
{
    /**
     * @param $listId           The unique id for the list.
     * @param $subscriberHash   The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $note        The content of the note.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($listId,$subscriberHash,$note=null)
    {
        $_params = array();
        if($note) $_params['note'] = $note;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash,$_params,Mailchimp::POST);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$subscriberHash,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash.'/notes',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param $noteId               The id for the note.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$subscriberHash,$noteId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash.'/notes/'.$noteId,$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param $noteId               The id for the note.
     * @param null $note        The content of the note.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($listId,$subscriberHash,$noteId,$note=null)
    {
        $_params = array();
        if($note) $_params['note'] = $note;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash.'/notes/'.$noteId,$_params,Mailchimp::PATCH);
    }
    public function delete($listId,$subscriberHash,$noteId)
    {
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash.'/notes/'.$noteId,null,Mailchimp::DELETE);
    }
}