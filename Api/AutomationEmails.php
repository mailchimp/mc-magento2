<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 4:12 PM
 * @file: AutomationEmails.php
 */
class Mailchimp_AutomationEmails extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_AutomationEmailsQueue
     */
    public $queue;

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($workflowId)
    {
        return $this->master->call('automation/'.$workflowId.'/emails',null, Mailchimp::GET);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($workflowId,$workflowEmailId)
    {
        return $this->master->call('automation/'.$workflowId.'/emails/'.$workflowEmailId,null, Mailchimp::GET);
    }

    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function pause($workflowId,$workflowEmailId)
    {
        return $this->master->call('automation/'.$workflowId.'/emails/'.$workflowEmailId.'/pause',null, Mailchimp::POST);
    }
    /**
     * @param $workflowId           The unique id for the Automation workflow.
     * @param $workflowEmailId      The unique id for the Automation workflow email.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function start($workflowId,$workflowEmailId)
    {
        return $this->master->call('automation/'.$workflowId.'/emails/'.$workflowEmailId.'/start',null, Mailchimp::POST);
    }
}
