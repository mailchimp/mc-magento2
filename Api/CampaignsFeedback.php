<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:18 PM
 * @file: CampaignsFeedback.php
 */
class Mailchimp_CampaignsFeedback extends Mailchimp_Abstract
{
    /**
     * @param $campaignId           The unique id for the campaign.
     * @param null $blockId         The block id for the editable block that the feedback addresses.
     * @param $message              The content of the feedback.
     * @param null $isComplete      The status of feedback.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($campaignId,$blockId=null,$message=null,$isComplete=null)
    {
        $_params=array('message'=>$message);
        if($blockId) $_params['block_id'] = $blockId;
        if($isComplete) $_params['is_complete'] = $isComplete;
        return $this->master->call('campaigns/'.$campaignId.'/feedback',$_params,Mailchimp::POST);
    }

    /**
     * @param $campaignId           The unique id for the campaign.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($campaignId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('campaigns/'.$campaignId.'/feedback',$_params,Mailchimp::GET);
    }

    /**
     * @param $campaignId               The unique id for the campaign.
     * @param $feedbackId               The unique id for the feedback message.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($campaignId,$feedbackId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('campaigns/'.$campaignId.'/feedback/'.$feedbackId,$_params,Mailchimp::GET);
    }

    /**
     * @param $campaignId       The unique id for the campaign.
     * @param $feedbackId       The unique id for the feedback message.
     * @param null $blockId     The block id for the editable block that the feedback addresses.
     * @param $message          The content of the feedback.
     * @param $isComplete       The status of feedback.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($campaignId,$feedbackId,$blockId=null,$message=null,$isComplete=null)
    {
        $_params=array('message'=>$message);
        if($blockId) $_params['block_id'] = $blockId;
        if($isComplete) $_params['is_complete'] = $isComplete;
        return $this->master->call('campaigns/'.$campaignId.'/feedback/'.$feedbackId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $campaignId       The unique id for the campaign.
     * @param $feedbackId       The unique id for the feedback message.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($campaignId,$feedbackId)
    {
        return $this->master->call('campaigns/'.$campaignId.'/feedback/'.$feedbackId,null,Mailchimp::DELETE);
    }
}