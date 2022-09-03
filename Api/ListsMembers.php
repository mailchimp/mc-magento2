<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 3:49 PM
 * @file: ListMembers.php
 */
class Mailchimp_ListsMembers extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ListsMembersActivity
     */
    public $memberActivity;
    /**
     * @var Mailchimp_ListsMembersGoals
     */
    public $memberGoal;
    /**
     * @var Mailchimp_ListsMembersNotes
     */
    public $memberNotes;

    /**
     * @param $listId               The unique id for the list.
     * @param null                                               $emailType   Type of email this member asked to get (‘html’ or ‘text’).
     * @param $status               Subscriber’s current status. (subscribed | unsubscribed | cleaned | pending)
     * @param $emailAddress        Subscriber's email address.
     * @param null                                               $mergeFields An individual merge var and value for a member.
     * @param null                                               $interests   The key of this object’s properties is the ID of the interest in question.
     * @param null                                               $language    If set/detected, the subscriber’s language.
     * @param null                                               $vip         VIP status for subscriber.
     * @param null                                               $location    Subscriber location information.
     * @param null                                               $ipOpt       The IP address the subscriber used to confirm their opt-in status.
     * @return mixed
     * @throws MailChimp_Error
     * @throws MailChimp_HttpError
     */
    public function add($listId, $status, $emailAddress, $emailType=null, $mergeFields=null, $interests=null,
                        $language=null, $vip=null, $location=null, $ipOpt=null
    ) {

        $_params = array('status'=>$status, 'email_address' => $emailAddress);
        if($emailType) { $_params['email_type'] = $emailType;
        }
        if($mergeFields) { $_params['merge_fields'] = $mergeFields;
        }
        if($interests) { $_params['interests'] = $interests;
        }
        if($language) { $_params['language'] = $language;
        }
        if($vip) { $_params['vip'] = $vip;
        }
        if($location) { $_params['location'] = $location;
        }
        if($ipOpt) { $_params['ip_opt'] = $ipOpt;
        }
        return $this->master->call('lists/'.$listId.'/members', $_params, Mailchimp::POST);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count               The number of records to return.
     * @param null $offset              The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @param null $emailType           The email type.
     * @param null $status              The subscriber’s status.
     * @param null $sinceTimpestampOpt  Restrict results to subscribers who opted-in after the set timeframe.
     * @param null $beforeTimestampOpt  Restrict results to subscribers who opted-in before the set timeframe.
     * @param null $sinceLastChanged    Restrict results to subscribers whose information changed after the set timeframe.
     * @param null $beforeLastChanged   Restrict results to subscribers whose information changed before the set timeframe.
     * @param null $uniqueEmailId       A unique identifier for the email address across all MailChimp lists. This parameter can be found in any links with
     *                                  Ecommerce 360 tracking enabled.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($listId,$fields=null,$excludeFields=null,$count=null,$offset=null,$emailType=null,$status=null,$sinceTimpestampOpt=null,
                        $beforeTimestampOpt=null,$sinceLastChanged=null,$beforeLastChanged=null,$uniqueEmailId=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        if($emailType) $_params['email_type'] = $emailType;
        if($status) $_params['status'] = $status;
        if($sinceTimpestampOpt) $_params['since_timpestamp_opt'] = $sinceTimpestampOpt;
        if($beforeTimestampOpt) $_params['before_timestamp_opt'] = $beforeTimestampOpt;
        if($sinceLastChanged) $_params['since_last_changed'] = $sinceLastChanged;
        if($beforeLastChanged) $_params['before_last_changed'] = $beforeLastChanged;
        if($uniqueEmailId) $_params['unique_email_id'] = $uniqueEmailId;
        return $this->master->call('lists/'.$listId.'/members',$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($listId,$subscriberHash,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash,$_params,Mailchimp::GET);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $emailType       Type of email this member asked to get (‘html’ or ‘text’).
     * @param null $status          Subscriber’s current status. (subscribed | unsubscribed | cleaned | pending)
     * @param null $mergeFields     An individual merge var and value for a member.
     * @param null $interests       The key of this object’s properties is the ID of the interest in question.
     * @param null $language        If set/detected, the subscriber’s language.
     * @param null $vip             VIP status for subscriber.
     * @param null $location        Subscriber location information.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function update($listId,$subscriberHash,$emailType=null,$status=null, $mergeFields=null,$interests=null,$language=null,$vip=null,$location=null)
    {
        $_params = array();
        if($status) $_params['status'] = $status;
        if($emailType) $_params['email_type'] = $emailType;
        if($mergeFields) $_params['merge_fields'] = $mergeFields;
        if($interests) $_params['interests'] = $interests;
        if($language) $_params['language'] = $language;
        if($vip) $_params['vip'] = $vip;
        if($location) $_params['location'] = $location;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @param null $emailType       Type of email this member asked to get (‘html’ or ‘text’).
     * @param $status               Subscriber’s current status. (subscribed | unsubscribed | cleaned | pending)
     * @param null $mergeFields     An individual merge var and value for a member.
     * @param null $interests       The key of this object’s properties is the ID of the interest in question.
     * @param null $language        If set/detected, the subscriber’s language.
     * @param null $vip             VIP status for subscriber.
     * @param null $location        Subscriber location information.
     * @param $emailAddress         Email address for a subscriber used only on a PUT request if the email is not already present on the list.
     * @param $statusIfNew          Subscriber’s status used only on a PUT request if the email is not already present on the list.
     *                              (subscribed | unsubscribed | cleaned | pending)
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function addOrUpdate($listId,$subscriberHash,$emailType=null,$status=null, $mergeFields=null,$interests=null,$language=null,$vip=null,$location=null,
                                $emailAddress=null,$statusIfNew=null)
    {
        $_params = array('status'=>$status,'email_address'=>$emailAddress);
        if($emailType) $_params['email_type'] = $emailType;
        if($mergeFields) $_params['merge_fields'] = $mergeFields;
        if($interests) $_params['interests'] = $interests;
        if($language) $_params['language'] = $language;
        if($vip) $_params['vip'] = $vip;
        if($location) $_params['location'] = $location;
        if($statusIfNew) $_params['status_if_new'] = $statusIfNew;
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash,$_params,Mailchimp::PUT);
    }

    /**
     * @param $listId               The unique id for the list.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($listId,$subscriberHash)
    {
        return $this->master->call('lists/'.$listId.'/members/'.$subscriberHash,null,Mailchimp::DELETE);
    }
}