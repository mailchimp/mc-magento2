<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 2:00 PM
 * @file: ListsSegments.php
 */
class Mailchimp_ListsSegments extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ListsSegmentsMembers
     */
    public $segmentMembers;

    public function getInformation($listId,$fields=null)
    {
        $_params = array();
        if($fields)
        {
            $_params['fields'] = $fields;
        }
        return $this->master->call('lists/'.$listId.'/segments',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null $count               The number of records to return.
     * @param null $offset              The number of records from a collection to skip. Iterating over large
     *                                  collections with this parameter can be slow.
     * @param null $type                Limit results based on segment type.
     * @param null $sinceCreatedAt      Restrict results to segments created after the set time.
     * @param null $beforeCreatedAt     Restrict results to segments created before the set time.
     * @param null $sinceUpdatedAt      Restrict results to segments update after the set time.
     * @param null $beforeUpdatedAt     Restrict results to segments update before the set time.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$fields=null,$excludeFields=null,$count=null,$offset=null,$type=null,
                           $sinceCreatedAt=null,$beforeCreatedAt=null,$sinceUpdatedAt=null,$beforeUpdatedAt=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        if($type) $_params['type'] = $type;
        if($sinceCreatedAt) $_params['since_created_at'] = $sinceCreatedAt;
        if($beforeCreatedAt) $_params['before_created_at'] = $beforeCreatedAt;
        if($sinceUpdatedAt) $_params['since_updated_at'] = $sinceUpdatedAt;
        if($beforeUpdatedAt) $_params['before_updated_at'] = $beforeUpdatedAt;
        return $this->master->call('lists/'.$listId.'/segments',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $segmentId            The unique id for the segment.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$segmentId)
    {
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId,null,Mailchimp::GET);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param $name                     The name of the segment.
     * @param null $staticSegment       An array of emails to be used for a static segment. Any emails provided that are
     *                                  not present on the list will be ignored. Passing an empty array will create a
     *                                  static segment without any subscribers. This field cannot be provided with the
     *                                  options field.
     * @param null $options
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($listId,$name,$staticSegment=null,$options=null)
    {
        $_params = array('name'=>$name);
        if($staticSegment) $_params['static_segment'] = $staticSegment;
        if($options) $_params['options'] = $options;
        return $this->master->call('lists/'.$listId.'/segments',$_params,Mailchimp::POST);
    }
    /**
     * @param $listId                   The unique id for the list.
     * @param $segmentId                The unique id for the segment.
     * @param $name                     The name of the segment.
     * @param null $staticSegment       An array of emails to be used for a static segment. Any emails provided that are
     *                                  not present on the list will be ignored. Passing an empty array will create a
     *                                  static segment without any subscribers. This field cannot be provided with the
     *                                  options field.
     * @param null $options
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($listId,$segmentId,$name,$staticSegment=null,$options=null)
    {
        $_params = array();
        if($name) $_params['name'] =$name;
        if($staticSegment) $_params['static_segment'] = $staticSegment;
        if($options) $_params['options'] = $options;
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param $segmentId                The unique id for the segment.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($listId,$segmentId)
    {
        return $this->master->call('lists/'.$listId.'/segments/'.$segmentId,null,Mailchimp::DELETE);
    }
}