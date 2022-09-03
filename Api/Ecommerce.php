<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/2/16 3:59 PM
 * @file: Ecommerce.php
 */
class Mailchimp_Ecommerce extends Mailchimp_Abstract
{
    /**
     * @var Mailchimp_EcommerceStores
     */
    public $stores;
    /**
     * @var Mailchimp_EcommerceCarts
     */
    public $carts;
    /**
     * @var Mailchimp_EcommerceCustomers
     */
    public $customers;
    /**
     * @var Mailchimp_EcommerceOrders
     */
    public $orders;
    /**
     * @var Mailchimp_EcommerceProducts
     */
    public $products;
    /**
     * @var Mailchimp_EcommercePromoRules
     */
    public $promoRules;
    /**
     * @var Mailchimp_EcommercePromoCodes
     */
    public $promoCodes;

}
