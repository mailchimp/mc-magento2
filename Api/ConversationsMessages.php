<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:26 PM
 * @file: ConversationsMessages.php
 */
class Mailchimp_ConversationsMessages extends Mailchimp_Abstract
{
    /**
     * @param $conversationId       The unique id for the conversation.
     * @param $fromEmail            A label representing the email of the sender of this message
     * @param null $subject         The subject of this message
     * @param null $message         The plain-text content of the message
     * @param $read                 Whether this message has been marked as read
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($conversationId,$fromEmail,$subject=null,$message=null,$read=null)
    {
        $_params = array('from_email'=>$fromEmail,'read'=>$read);
        if($subject) $_params['subjects'] = $subject;
        if($message) $_params['message'] = $message;
        return $this->master->call('conversations/'.$conversationId.'/messages',$_params,Mailchimp::POST);
    }

    /**
     * @param $conversationId           The unique id for the conversation.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $isRead              Whether a conversation message has been marked as read.
     * @param null $beforeTimestamp     Restrict the response to messages created before the set time. We recommend ISO 8601 time format: 2015-10-21T15:41:36+00:00.
     * @param null $sinceTimestamp      Restrict the response to messages created after the set time. We recommend ISO 8601 time format: 2015-10-21T15:41:36+00:00.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($conversationId,$fields=null,$excludeFields=null,$isRead=null,$beforeTimestamp=null,$sinceTimestamp=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($isRead) $_params['is_read'] = $isRead;
        if($beforeTimestamp) $_params['before_timestamp'] = $beforeTimestamp;
        if($sinceTimestamp) $_params['since_timestamp'] = $sinceTimestamp;
        return $this->master->call('conversation/'.$conversationId.'/messages',$_params,Mailchimp::GET);
    }

    /**
     * @param $conversationId           The unique id for the conversation.
     * @param $messageId                The unique id for the conversation message.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($conversationId,$messageId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('conversation/'.$conversationId.'/messages/'.$messageId,$_params,Mailchimp::GET);
    }
}