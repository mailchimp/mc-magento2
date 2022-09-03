<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/29/16 4:15 PM
 * @file: EcommerceStoresCustomers.php
 */
class Mailchimp_EcommerceCustomers extends Mailchimp_Abstract
{
    /**
     * @param $storeId          The store id.
     * @param $id               A unique identifier for the customer.
     * @param $emailAddress     The customer’s email address.
     * @param $optInStatus      The customer’s opt-in status. This value will never overwrite the opt-in status of a pre-existing MailChimp list member,
     *                          but will apply to list members that are added through the e-commerce API endpoints.
     * @param null $company     The customer’s company.
     * @param null $firstName   The customer’s first name.
     * @param null $lastName    The customer’s last name.
     * @param null $ordersCount The customer’s total order count.
     * @param null $totalSpent  The total amount the customer has spent.
     * @param null $address     The customer’s address.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function add($storeId,$id,$emailAddress,$optInStatus,$company=null,$firstName=null,$lastName=null,$ordersCount=null,$totalSpent=null,$address=null)
    {
        $_params = array('id'=>$id,'email_address'=>$emailAddress,'opt_in_status'=>$optInStatus);
        if($company) $_params['company'] = $company;
        if($firstName) $_params['first_name'] = $firstName;
        if($lastName) $_params['last_name'] = $lastName;
        if($ordersCount) $_params['orders_count'] = $ordersCount;
        if($totalSpent) $_params['total_spent'] = $totalSpent;
        if($address) $_params['address'] = $address;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers',$_params,Mailchimp::POST);
    }

    /**
     * @param $storeId              The store id.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @param null $count           The number of records to return.
     * @param null $offset          The number of records from a collection to skip. Iterating over large collections with this parameter can be slow.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getAll($storeId,$fields=null,$excludeFields=null,$count=null,$offset=null)
    {
        $_params = array();
        if($fields) $_params['fields'] = $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        if($count) $_params['count'] = $count;
        if($offset) $_params['offset'] = $offset;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers',$_params,Mailchimp::GET);
    }

    /**
     * @param $storeId              The store id.
     * @param $customerId           The id for the customer of a store.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function get($storeId,$customerId,$fields=null,$excludeFields=null)
    {
        $_params = array();
        if($fields) $_params['fields']= $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers/'.$customerId,$_params,Mailchimp::GET);
    }
    /**
     * @param $storeId              The store id.
     * @param $customerEmail        The email for the customer of a store.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects
     *                              with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                              with dot notation.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function getByEmail($storeId,$customerEmail,$fields=null,$excludeFields=null)
    {
        $_params = array('email_address'=>$customerEmail);
        if($fields) $_params['fields']= $fields;
        if($excludeFields) $_params['exclude_fields'] = $excludeFields;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers', $_params, Mailchimp::GET);
    }
    /**
     * @param $storeId          The store id.
     * @param $customerId       A unique identifier for the customer.
     * @param $optInStatus      The customer’s opt-in status. This value will never overwrite the opt-in status of a pre-existing MailChimp list member,
     *                          but will apply to list members that are added through the e-commerce API endpoints.
     * @param null $company     The customer’s company.
     * @param null $firstName   The customer’s first name.
     * @param null $lastName    The customer’s last name.
     * @param null $ordersCount The customer’s total order count.
     * @param null $totalSpent  The total amount the customer has spent.
     * @param null $address     The customer’s address.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function modify($storeId,$customerId,$optInStatus=null,$company=null,$firstName=null,$lastName=null,$ordersCount=null,
                           $totalSpent=null,$address=null)
    {
        $_params = array();
        if($optInStatus) $_params['opt_in_status'] = $optInStatus;
        if($company) $_params['company'] = $company;
        if($firstName) $_params['first_name'] = $firstName;
        if($lastName) $_params['last_name'] = $lastName;
        if($ordersCount) $_params['orders_count'] = $ordersCount;
        if($totalSpent) $_params['total_spent'] = $totalSpent;
        if($address) $_params['address'] = $address;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers/'.$customerId,$_params,Mailchimp::PATCH);
    }

    /**
     * @param $storeId          The store id.
     * @param $customerId       A unique identifier for the customer.
     * @param $emailAddress     The customer’s email address.
     * @param $optInStatus      The customer’s opt-in status. This value will never overwrite the opt-in status of a pre-existing MailChimp list member,
     *                          but will apply to list members that are added through the e-commerce API endpoints.
     * @param null $company     The customer’s company.
     * @param null $firstName   The customer’s first name.
     * @param null $lastName    The customer’s last name.
     * @param null $ordersCount The customer’s total order count.
     * @param null $totalSpent  The total amount the customer has spent.
     * @param null $address     The customer’s address.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function addOrModify($storeId,$customerId,$emailAddress,$optInStatus,$company=null,$firstName=null,$lastName=null,$ordersCount=null,$totalSpent=null,$address=null)
    {
        $_params = array('id'=>$customerId,'email_address'=>$emailAddress,'opt_in_status'=>$optInStatus);
        if($company) $_params['company'] = $company;
        if($firstName) $_params['first_name'] = $firstName;
        if($lastName) $_params['last_name'] = $lastName;
        if($ordersCount) $_params['orders_count'] = $ordersCount;
        if($totalSpent) $_params['total_spent'] = $totalSpent;
        if($address) $_params['address'] = $address;
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers/'.$customerId,$_params,Mailchimp::PUT);
    }

    /**
     * @param $storeId          The store id.
     * @param $customerId       A unique identifier for the customer.
     * @return mixed
     * @throws Mailchimp_Error
     * @throws Mailchimp_HttpError
     */
    public function delete($storeId,$customerId)
    {
        return $this->master->call('ecommerce/stores/'.$storeId.'/customers/'.$customerId,null,Mailchimp::DELETE);
    }
}