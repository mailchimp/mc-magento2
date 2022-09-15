<?php
/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 15/09/22 12:07 PM
 * @file: Months.php
 */
namespace Ebizmarts\MailChimp\Model\Config\Source;

class Months implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return ["0" => __("No"), "1"=> "1", "2" =>"2", "3" => "3", "4" => "4"];
    }
}
