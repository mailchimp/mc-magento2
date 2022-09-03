<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:19 PM
 * @file: CampaignsSendChecklist.php
 */
class Mailchimp_CampaignsSendChecklist extends Mailchimp_Abstract
{
    /**
     * @param $campaignId               The unique id for the campaign.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     * @throws Mailchimp_HttpError
     */
    public function get($campaignId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('campaigns/'.$campaignId.'/send-checklist',$_params,Mailchimp::GET);
    }

}