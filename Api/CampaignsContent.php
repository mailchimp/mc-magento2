<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:16 PM
 * @file: CampaignsContent.php
 */
class Mailchimp_CampaignsContent extends Mailchimp_Abstract
{
    /**
     * @param string $campaignId    The unique id for the campaign.
     * @param null $fields
     * @param null $excludeFields
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($campaignId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('campaigns/'.$campaignId.'/content',$_params,Mailchimp::GET);
    }

    /**
     * @param string $campaignId    The unique id for the campaign.
     * @param null $plainText       The plain-text portion of the campaign. If left unspecified, we’ll generate this automatically.
     * @param null $html            The raw HTML for the campaign.
     * @param null $url             When importing a campaign, the URL where the HTML lives.
     * @param null $template        Use this template to generate the HTML content of the campaign
     * @param null $archive         Available when uploading an archive to create campaign content. The archive should include all
     *                              campaign content and images
     * @param null $variateContents Content options for Multivariate Campaigns. Each content option must provide HTML content and
     *                              may optionally provide plain text. For campaigns not testing content, only one object should be provided.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($campaignId,$plainText=null,$html=null,$url=null,$template=null,$archive=null,$variateContents=null)
    {
        $_params = array();
        if($plainText) $_params['plain_text'] = $plainText;
        if($html) $_params['html'] = $html;
        if($url) $_params['url'] = $url;
        if($template) $_params['template'] = $template;
        if($archive) $_params['archive'] = $archive;
        if($variateContents) $_params['variate_contents'] = $variateContents;
        return $this->master->call('campaigns/'.$campaignId.'/content',$_params,Mailchimp::PUT);
    }
}