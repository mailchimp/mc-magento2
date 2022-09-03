<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:13 PM
 * @file: AutomationEmailsQueue.php
 */
class Mailchimp_AutomationEmailsQueue extends Mailchimp_Abstract
{
    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @param $emailAddress         The list member’s email address.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($workflowId,$workflowEmailId,$emailAddress)
    {
        $_params = array('email_address'=>$emailAddress);
        $this->master->call('automations/'.$workflowId.'/emails/'.$workflowEmailId.'/queue',$_params,Mailchimp::POST);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($workflowId,$workflowEmailId)
    {
        $this->master->call('automations/'.$workflowId.'/emails/'.$workflowEmailId.'/queue',null,Mailchimp::GET);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @param $subscriberHash       The MD5 hash of the lowercase version of the list member’s email address.
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($workflowId,$workflowEmailId,$subscriberHash)
    {
        $this->master->call('automations/'.$workflowId.'/emails/'.$workflowEmailId.'/queue/'.$subscriberHash,null,Mailchimp::GET);
    }
}
