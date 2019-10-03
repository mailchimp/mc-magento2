<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/30/17 10:35 AM
 * @file: VarsMap.php
 */

namespace Ebizmarts\MailChimp\Helper;

class VarsMap extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Data
     */
    protected $_helper;
    protected $mathRandom;

    /**
     * VarsMap constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Math\Random $mathRandom,
        \Ebizmarts\MailChimp\Helper\Data $helper
    ) {
    
        $this->_helper      = $helper;
        $this->mathRandom   = $mathRandom;
        parent::__construct($context);
    }
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        $value = $this->encodeArrayFieldValue($value);
        return $value;
    }
    public function makeStorableArrayFieldValue($value)
    {
        $value = is_array($value) ? $this->decodeArrayFieldValue($value) : $value;
        $value = $this->serializeValue($value);
        return $value;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('customer_field_id', $row)
                || !array_key_exists('mailchimp_field_id', $row)
            ) {
                return false;
            }
        }
        return true;
    }
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('customer_field_id', $row)
                || !array_key_exists('mailchimp_field_id', $row)
            ) {
                continue;
            }
            $customer_field_id = $row['customer_field_id'];
            $mailchimp_field_id = $row['mailchimp_field_id'];
            $result[$customer_field_id] = $mailchimp_field_id;
        }
        return $result;
    }
    protected function serializeValue($value)
    {
        $rc = '';
        if (is_array($value)) {
            $data = [];
            foreach ($value as $customerFiledId => $mailchimpName) {
                if (!array_key_exists($customerFiledId, $data)) {
                    $data[$customerFiledId] = $mailchimpName;
                }
            }
            try {
                $rc = $this->_helper->serialize($data);
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return $rc;
    }
    protected function unserializeValue($value)
    {
        $rc = [];
        if (is_string($value) && !empty($value)) {
            try {
                $rc = $this->_helper->unserialize($value);
            } catch (\Exception $e) {
                $this->_helper->log($e->getMessage());
            }
        }
        return $rc;
    }
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $customerFieldId => $mailchimpName) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['mailchimp_field_id' => $mailchimpName ,'customer_field_id' => $customerFieldId];
        }
        return $result;
    }
}
