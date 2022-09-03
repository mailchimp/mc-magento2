<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:58 PM
 * @file: Conversations.php
 */
class Mailchimp_Conversations extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ConversationsMessages
     */
    public $messages;

    /**
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @param $hasUnreadMessages    Whether the conversation has any unread messages.
     * @param $listId               The unique id for the list.
     * @param $campaignId           The unique id for the campaign.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($fields=null,$excludeFields=null,$count=null,$offset=null,$hasUnreadMessages=null,$listId=null,
                           $campaignId=null)
    {
        $_params = array();
        if ($fields) $_params['fields'] = $fields;
        if ($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if ($count) $_params['count'] = $count;
        if ($offset) $_params['offset'] = $offset;
        if($hasUnreadMessages) $_params['has_unread_messages'] = $hasUnreadMessages;
        if($listId) $_params['list_id'] = $listId;
        if($campaignId) $_params['campaign_id'] = $campaignId;
        return $this->master->call('conversations',$_params,Mailchimp::GET);
    }

    /**
     * @param $conversationId       The unique id for the conversation.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($conversationId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if ($fields) $_params['fields'] = $fields;
        if ($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('conversations/'.$conversationId,$_params,Mailchimp::GET);
    }
}