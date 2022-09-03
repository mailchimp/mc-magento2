<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:53 PM
 * @file: CampaignFolders.php
 */
class Mailchimp_CampaignFolders extends Mailchimp_Abstract
{
    /**
     * @param $name                 Name to associate with the folder.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($name)
    {
        $_params = array('name'=>$name);
        return $this->master->call('campaign-folders',$_params,Mailchimp::POST);
    }

    /**
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count               The number of records to return.
     * @param null $offset              The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if ($fields) $_params['fields'] = $fields;
        if ($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if ($count) $_params['count'] = $count;
        if ($offset) $_params['offset'] = $offset;
        return $this->master->call('campaign-folders',$_params,Mailchimp::GET);
    }

    /**
     * @param $folderId             The unique id for the campaign folder.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($folderId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if ($fields) $_params['fields'] = $fields;
        if ($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('campaign-folders/'.$folderId,$_params,Mailchimp::GET);
    }

    /**
     * @param $folderId             The unique id for the campaign folder.
     * @param $name                 Name to associate with the folder.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($folderId,$name)
    {
        $_params = array('name'=>$name);
        return $this->master->call('campaign-folders/'.$folderId,$_params,Mailchimp::PATCH);
    }
    public function delete($folderId)
    {
        return $this->master->call('campaign-folders/'.$folderId,null,Mailchimp::DELETE);
    }
}