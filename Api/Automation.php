<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 3:47 PM
 * @file: Automation.php
 */
class Mailchimp_Automation extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_AutomationEmails
     */
    public $emails;
    /**
     * @var Mailchimp_AutomationRemovedSubscribers
     */
    public $removedSubscribers;

    /**
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('automations',$_params,Mailchimp::GET);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($workflowId)
    {
        return $this->master->call('automation/'.$workflowId,null, Mailchimp::GET);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function pause($workflowId)
    {
        return $this->master->call('automation/'.$workflowId.'/pause-all-emails',null, Mailchimp::POST);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function start($workflowId)
    {
        return $this->master->call('automation/'.$workflowId.'/start-all-emails',null, Mailchimp::POST);
    }
}