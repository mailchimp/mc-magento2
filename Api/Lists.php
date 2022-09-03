<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 5:00 PM
 * @file: Lists.php
 */

class Mailchimp_Lists extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_ListsSegments
     */
    public $segments;
    /**
     * @var Mailchimp_ListsAbuseReports
     */
    public $abuseReports;
    /**
     * @var Mailchimp_ListsActivity
     */
    public $activity;
    /**
     * @var Mailchimp_ListsClients
     */
    public $clients;
    /**
     * @var Mailchimp_ListsGrowthHistory
     */
    public $growthHistory;
    /**
     * @var Mailchimp_ListsInterestCategory
     */
    public $interestCategory;
    /**
     * @var Mailchimp_ListsMembers
     */
    public $members;
    /**
     * @var Mailchimp_ListsMergeFields
     */
    public $mergeFields;
    /**
     * @var Mailchimp_ListsWebhooks
     */
    public $webhooks;

    /**
     * @param $name
     * @param $contact
     *          company *   (The company name for the list)
     *          address1 *  (The street address for the list contact)
     *          address2    (The street address for the list contact)
     *          city *      (The city for the list contact)
     *          state *     (The state for the list contact)
     *          zip *       (The postal or zip code for the list contact)
     *          country *   (A two-character ISO3166 country code. Defaults to US if invalid.)
     * @param $permissionRemanider
     * @param bool|false $useArchiveBar
     * @param $campaingDefaults
     *          fromName * (The default from name for campaigns sent to this list)
     *          fromEmail * (The email address to send unsubscribe notifications to)
     *          subject * (The default subject line for campaigns sent to this list)
     *          language *(The default language for this lists’s forms)
     * @param bool|false $notifiyOnSubscribe
     * @param $notifyOnUnsubscribe
     * @param $emailTypeOption
     * @param string $visibility
     */
    public function add($name,$contact,$permissionRemanider,$useArchiveBar=false,$campaingDefaults=null,$notifyOnSubscribe=false,$notifyOnUnsubscribe=null,$emailTypeOption=null,
                        $visibility='pub')
    {
        $_params= array('name'=>$name,'contact'=>$contact,'permission_reminder'=>$permissionRemanider,'use_archive_bar'=>$useArchiveBar,
            'campaign_defaults'=>$campaingDefaults,'notify_on_subscribe'=>$notifyOnSubscribe,'notify_on_unsubscribe'=>$notifyOnUnsubscribe,
            'email_type_option'=>$emailTypeOption,'visibility'=>$visibility);
        return $this->master->call('lists',$_params,Mailchimp::POST);
    }
    public function getLists($id=null,$fields=null,$excludeFields=null,$count=null,$offset=null,$beforeDateCreated=null,$sinceDateCreated=null,
                                $beforeCampaignLastSent=null,$sinceCampaignLastSent=null,$email=null)
    {
        $_params = array();
        if($fields)
        {
            $_params['fields'] = $fields;
        }
        if($excludeFields)
        {
            $_params['exclude_fields'] = $excludeFields;
        }
        if($count)
        {
            $_params['count'] = $count;
        }
        if($offset)
        {
            $_params['offset'] = $offset;
        }
        if($beforeDateCreated)
        {
            $_params['before_date_created'] = $beforeDateCreated;
        }
        if($sinceDateCreated)
        {
            $_params['since_date_created'] = $sinceDateCreated;
        }
        if($beforeCampaignLastSent)
        {
            $_params['before_campaigns_last_sent'] = $beforeCampaignLastSent;
        }
        if($sinceCampaignLastSent)
        {
            $_params['since_campaign_last_sent'] = $sinceCampaignLastSent;
        }
        if($email)
        {
            $_params['email'] = $email;
        }
        if($id) {
            return $this->master->call('lists/'.$id, $_params, Mailchimp::GET);
        }
        else
        {
            return $this->master->call('lists/', $_params, Mailchimp::GET);
        }
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param $name                     The name of the list.
     * @param $contact                  Contact information displayed in campaign footers to comply with international spam laws.
     * @param $permissionRemainder      The permission reminder for the list.
     * @param null $useArchiveBar       Whether campaigns for this list use the Archive Bar in archives by default.
     * @param null $campaignDefaults    Default values for campaigns created for this list.
     * @param null $notifyOnSubscribe   The email address to send subscribe notifications to.
     * @param null $notifyOnUnsubscribe The email address to send unsubscribe notifications to.
     * @param $emailTypeOption          Whether the list supports multiple formats for emails.
     *                                  When set to true, subscribers can choose whether they want to receive HTML or plain-text emails.
     *                                  When set to false, subscribers will receive HTML emails, with a plain-text alternative backup.
     * @param null $visibility          Whether this list is public or private. (pub/prv)
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function edit($listId,$name,$contact,$permissionRemainder,$useArchiveBar=null,$campaignDefaults=null,$notifyOnSubscribe=null,$notifyOnUnsubscribe=null,
                         $emailTypeOption=null,$visibility=null)
    {
        $_params = array('name'=>$name,'contact'=>$contact,'permission_reminder'=>$permissionRemainder,'email_type_option'=>$emailTypeOption);
        if($useArchiveBar) $_params['use_archive_bar'] = $useArchiveBar;
        if($campaignDefaults) $_params['campaign_defaults'] = $campaignDefaults;
        if($notifyOnSubscribe) $_params['notify_on_subscribe'] = $notifyOnSubscribe;
        if($notifyOnUnsubscribe) $_params['notify_on_unsubscribe'] = $notifyOnUnsubscribe;
        if($visibility) $_params['visibility'] = $visibility;
        return $this->master->call('lists/'.$listId, $_params, Mailchimp::PATCH);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($listId)
    {
        return $this->master->call('lists/'.$listId, null, Mailchimp::DELETE);
    }
}