<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 10/17/17 2:38 PM
 * @file: PromoRules.php
 */

namespace Ebizmarts\MailChimp\Model\Api;

class PromoRules
{
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';
    const TARGET_SHIPPING = 'shipping';

    private $_batchId;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    private $_collection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $_date;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    private $_helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory
     */
    private $_chimpSyncEcommerce;
    /**
     * @var \Magento\SalesRule\Model\RuleRepository
     */
    private $_ruleRepo;

    /**
     * PromoRules constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collection
     * @param \Magento\SalesRule\Model\RuleRepository $ruleRepo
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collection,
        \Magento\SalesRule\Model\RuleRepository $ruleRepo,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->_helper              = $helper;
        $this->_collection          = $collection;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_date                = $date;
        $this->_ruleRepo             = $ruleRepo;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE. '_' . $this->_date->gmtTimestamp();
    }
    public function getNewPromoRule($ruleId,$mailchimpStoreId,$magentoStoreId)
    {
        $data = [];
        $rule = $this->_ruleRepo->getById($ruleId);
        try {
            $promoRulesJson = json_encode($this->_generateRuleData($rule));
            if(!empty($promoRulesJson)) {
                $data['method'] = 'POST';
                $data['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules';
                $data['operation_id'] = $this->_batchId. '_' .$ruleId;
                $data['body'] = $promoRulesJson;
                $this->_updateSyncData($mailchimpStoreId, $ruleId, $this->_date->gmtDate());
            } else {
                $error = __('Something went wrong when retrieving the information.');
                $this->_updateSyncData($mailchimpStoreId, $ruleId, $this->_date->gmtDate(), $error);
            }
        } catch(Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $data;
    }
    private function _generateRuleData($rule)
    {
        $data = [];
        $data['id'] = $rule->getRuleId();
        $data['title'] = $rule->getName();
        $data['description'] = $rule->getDescription() ? $rule->getDescription() : $rule->getName();
        $fromDate = $rule->getFromDate();
        if($fromDate) {
            $data['starts_at'] = $fromDate;
        }
        $toDate = $rule->getToDate();
        if($toDate) {
            $data['ends_at'] = $toDate;
        }
        $data['amount'] = $rule->getDiscountAmount();
        $promoAction = $rule->getSimpleAction();
        $data['type'] = $this->_getMailChimpType($promoAction);
        if($data['type']==self::TYPE_PERCENTAGE) {
            $data['amount'] = $rule->getDiscountAmount()/100;
        } else {
            $data['amount'] = $rule->getDiscountAmount();
        }
        $data['target'] = $this->_getMailChimpTarget($promoAction);
        $data['enabled'] = (bool)$rule->getIsActive();

        return $data;
    }

    /**
     * @param $action
     * @return null|string
     */
    private function _getMailChimpType($action)
    {
        $mailChimpType = null;
        switch ($action) {
            case \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION:
                $mailChimpType = self::TYPE_PERCENTAGE;
                break;
            case \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION:
            case \Magento\SalesRule\Model\Rule::CART_FIXED_ACTION:
                $mailChimpType = self::TYPE_FIXED;
                break;
        }
        return $mailChimpType;
    }

    /**
     * @param $action
     * @return null|string
     */
    private function _getMailChimpTarget($action)
    {
        $mailChimpTarget = null;
        switch ($action) {
            case \Magento\SalesRule\Model\Rule::CART_FIXED_ACTION:
            case \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION:
                $mailChimpTarget = self::TARGET_TOTAL;
                break;
            case \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION:
                $mailChimpTarget = self::TARGET_PER_ITEM;
                break;
        }
        return $mailChimpTarget;
    }

    /**
     * @param $storeId
     * @param $entityId
     * @param $sync_delta
     * @param string $sync_error
     * @param int $sync_modified
     */
    protected function _updateSyncData($storeId, $entityId, $sync_delta, $sync_error = '', $sync_modified = 0)
    {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            $sync_delta,
            $sync_error,
            $sync_modified,
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE
        );
    }

}