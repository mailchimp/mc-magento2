<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:41 PM
 * @file: FileManagerFiles.php
 */
class Mailchimp_FileManagerFiles extends Mailchimp_Abstract
{
    /**
     * @param null $folderId        The id of the folder.
     * @param $name                 The name of the file.
     * @param $fileData             The base64-encoded contents of the file.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($folderId=null,$name=null,$fileData=null)
    {
        $_params=array('name'=>$name,'file_data'=>$fileData);
        if($folderId) $_params['folder_id'] = $folderId;
        return $this->master->call('file-manager/files',$_params,Mailchimp::POST);
    }

    /**
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null $count               The number of records to return.
     * @param null $offset              The number of records from a collection to skip. Iterating over large collections
     *                                  with this parameter can be slow.
     * @param null $type                The file type for the File Manager file.
     * @param null $createdBy           The MailChimp account user who created the File Manager file.
     * @param null $beforeCreatedAt     Restrict the response to files created before the set date.
     * @param null $sinceCreatedAt      Restrict the response to files created after the set date.
     * @param null $sortField           Returns files sorted by the specified field.
     * @param null $sortDir             Determines the order direction for sorted results.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($fields=null,$excludeFields=null,$count=null,$offset=null,$type=null,$createdBy=null,
                           $beforeCreatedAt=null,$sinceCreatedAt=null,$sortField=null,$sortDir=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        if($type) $_params['type'] = $type;
        if($createdBy) $_params['created_by'] = $createdBy;
        if($beforeCreatedAt) $_params['before_created_at'] = $beforeCreatedAt;
        if($sinceCreatedAt) $_params['since_created_at'] = $sinceCreatedAt;
        if($sortField) $_params['sort_field'] = $sortField;
        if($sortDir) $_params['sort_dir'] = $sortDir;
        return $this->master->call('file-manager/files',$_params,Mailchimp::GET);
    }

    /**
     * @param $fileId                   The unique id for the File Manager file.
     * @param null $fields              A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                  with dot notation.
     * @param null $excludeFields       A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                  with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($fileId,$fields=null,$excludeFields=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('file-manager/files/'.$fileId,$_params,Mailchimp::GET);
    }

    /**
     * @param $fileId                   The unique id for the File Manager file.
     * @param null $folderId            The id of the folder.
     * @param $name                     The name of the file.
     * @param $fileData                 The base64-encoded contents of the file.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($fileId,$folderId=null,$name=null,$fileData=null)
    {
        $_params=array('name'=>$name,'file_data'=>$fileData);
        if($folderId) $_params['folder_id'] = $folderId;
        return $this->master->call('file-manager/files/'.$fileId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $fileId                   The unique id for the File Manager file.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($fileId)
    {
        return $this->master->call('file-manager/files/'.$fileId,null,Mailchimp::DELETE);
    }
}