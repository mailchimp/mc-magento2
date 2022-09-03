<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 5:23 PM
 * @file: Templates.php
 */
class Mailchimp_Templates extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_TemplatesDefaultContent
     */
    public $defaultContent;

    /**
     * @param int $id                   The store id.
     * @param string[] $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param string[] $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param int $count                The number of records to return.
     * @param int $offset               The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws \Mailchimp_Error
     * @throws \Mailchimp_HttpError
     */
    public function get($id,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('templates/'.$id, $_params, \Mailchimp::GET);
    }

    /**
     * @param string[] $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param string[] $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param int $count                The number of records to return.
     * @param int $offset               The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws \Mailchimp_Error
     * @throws \Mailchimp_HttpError
     */
    public function getAll($fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params=array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('templates', $_params, \Mailchimp::GET);
    }
}