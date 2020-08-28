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

use Magento\Cms\Test\Unit\Controller\Adminhtml\Page\MassEnableTest;

class PromoRules
{
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';
    const TARGET_SHIPPING = 'shipping';
    const FREESHIPPING_NO = 0;
    const FREESHIPPING_FOR_MATHINGI_TEMS_ONLY = 1;
    const FREESHIPPING_FOR_SHIPMENT_WITH_MATCHING_ITEMS = 3;
    const MAX = 100;

    private $_batchId;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    private $_collection;
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
     * @var \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory
     */
    protected $_syncCollection;

    /**
     * PromoRules constructor.
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collection
     * @param \Magento\SalesRule\Model\RuleRepository $ruleRepo
     * @param \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce
     * @param \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $syncCollection
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collection,
        \Magento\SalesRule\Model\RuleRepository $ruleRepo,
        \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerceFactory $chimpSyncEcommerce,
        \Ebizmarts\MailChimp\Model\ResourceModel\MailChimpSyncEcommerce\CollectionFactory $syncCollection
    ) {
    
        $this->_helper              = $helper;
        $this->_collection          = $collection;
        $this->_chimpSyncEcommerce  = $chimpSyncEcommerce;
        $this->_ruleRepo             = $ruleRepo;
        $this->_batchId             = \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE. '_' .
            $this->_helper->getGmtTimeStamp();
        $this->_syncCollection      = $syncCollection;
    }
    public function sendRules($magentoStoreId)
    {
        $mailchimpStoreId = $this->_helper->getConfigValue(
            \Ebizmarts\MailChimp\Helper\Data::XML_MAILCHIMP_STORE,
            $magentoStoreId
        );
        $batchArray = [];

        $batchArray = array_merge($batchArray, $this->_getDeletedPromoRules($mailchimpStoreId, $magentoStoreId));
        $batchArray = array_merge($batchArray, $this->_getModifiedPromoRules($mailchimpStoreId, $magentoStoreId));
        return $batchArray;
    }
    protected function _getDeletedPromoRules($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = [];
        $collection = $this->_syncCollection->create();
        $collection->addFieldToFilter('mailchimp_store_id', ['eq'=>$mailchimpStoreId])
            ->addFieldToFilter('type', ['eq'=>\Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE])
            ->addFieldToFilter('mailchimp_sync_deleted', ['eq'=>1]);
        $collection->getSelect()->limit(self::MAX);
        $count = 0;
        $api = $this->_helper->getApi($magentoStoreId);
        /**
         * @var $rule \Ebizmarts\MailChimp\Model\MailChimpSyncEcommerce
         */
        foreach ($collection as $rule) {
            $ruleId = $rule->getData('related_id');
            try {
                $mailchimpRule = $api->ecommerce->promoCodes->getAll($mailchimpStoreId, $ruleId);
                foreach ($mailchimpRule['promo_codes'] as $promoCode) {
                    $this->_helper->ecommerceDeleteAllByIdType(
                        $promoCode['id'],
                        \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_CODE,
                        $mailchimpStoreId
                    );
                }
                $batchArray[$count]['method'] = 'DELETE';
                $batchArray[$count]['path'] = "/ecommerce/stores/$mailchimpStoreId/promo-rules/$ruleId";
                $batchArray[$count]['operation_id'] = $this->_batchId . '_' . $ruleId;
                $count++;
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }
            $this->_helper->ecommerceDeleteAllByIdType(
                $ruleId,
                \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE,
                $mailchimpStoreId
            );
        }
        return $batchArray;
    }
    protected function _getModifiedPromoRules($mailchimpStoreId, $magentoStoreId)
    {
        $batchArray = [];
        $websiteId = $this->_helper->getWebsiteId($magentoStoreId);
        /**
         * @var $collection \Magento\SalesRule\Model\ResourceModel\Rule\Collection
         */
        $collection = $this->_collection->create();
        $collection->addWebsiteFilter($websiteId);
        $collection->getSelect()->joinLeft(
            ['m4m' => $this->_helper->getTableName('mailchimp_sync_ecommerce')],
            "m4m.related_id = main_table.rule_id and m4m.type = '".
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE.
            "' and m4m.mailchimp_store_id = '".$mailchimpStoreId."'",
            ['m4m.*']
        );
        $collection->getSelect()->where("m4m.mailchimp_sync_modified = 1");
        $collection->getSelect()->limit(self::MAX);
        $count = 0;
        /**
         * @var $rule \Magento\SalesRule\Model\Rule
         */
        $api = $this->_helper->getApi($magentoStoreId);
        foreach ($collection as $rule) {
            $ruleId = $rule->getRuleId();
            try {
                $mailchimpRule = $api->ecommerce->promoCodes->getAll($mailchimpStoreId, $ruleId);
                foreach ($mailchimpRule['promo_codes'] as $promoCode) {
                    $this->_helper->ecommerceDeleteAllByIdType(
                        $promoCode['id'],
                        \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_CODE,
                        $mailchimpStoreId
                    );
                }
            } catch (\Mailchimp_Error $e) {
                $this->_helper->log($e->getFriendlyMessage());
            }
            $this->_helper->ecommerceDeleteAllByIdType(
                $rule->getRuleId(),
                \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE,
                $mailchimpStoreId
            );
            $batchArray[$count]['method'] = 'DELETE';
            $batchArray[$count]['path'] = "/ecommerce/stores/$mailchimpStoreId/promo-rules/$ruleId";
            $batchArray[$count]['operation_id'] = $this->_batchId. '_' . $rule->getRuleId();
            $count++;
        }
        return $batchArray;
    }
    public function getNewPromoRule($ruleId, $mailchimpStoreId, $magentoStoreId)
    {
        $data = [];
        /**
         * @var $rule \Magento\SalesRule\Model\Rule
         */
        try {
            $rule = $this->_ruleRepo->getById($ruleId);
            $promoRules = $this->_generateRuleData($rule);
            if (!empty($promoRules)) {
                $promoRulesJson = json_encode($promoRules);
                if ($promoRulesJson !== false) {
                    if (!empty($promoRulesJson)) {
                        $data['method'] = 'POST';
                        $data['path'] = '/ecommerce/stores/' . $mailchimpStoreId . '/promo-rules';
                        $data['operation_id'] = $this->_batchId . '_' . $ruleId;
                        $data['body'] = $promoRulesJson;
                        $this->_updateSyncData($mailchimpStoreId, $ruleId);
                    } else {
                        $error = __('Something went wrong when retrieving the information.');
                        $this->_updateSyncData(
                            $mailchimpStoreId,
                            $ruleId,
                            $this->_helper->getGmtDate(),
                            $error,
                            0
                        );
                    }
                } else {
                    $error = json_last_error_msg();
                    $this->_updateSyncData(
                        $mailchimpStoreId,
                        $ruleId,
                        $this->_helper->getGmtDate(),
                        $error,
                        0
                    );
                }
            } else {
                $error = __('Something went wrong when retrieving the information.');
                $this->_updateSyncData(
                    $mailchimpStoreId,
                    $ruleId,
                    $this->_helper->getGmtDate(),
                    $error,
                    0
                );
            }
        } catch (\Exception $e) {
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
        if ($fromDate) {
            $data['starts_at'] = $fromDate;
        }
        $toDate = $rule->getToDate();
        if ($toDate) {
            $data['ends_at'] = $toDate;
        }
        $promoAction = $rule->getSimpleAction();
        $shipping = $rule->getSimpleFreeShipping();
        $data['type'] = $this->_getMailChimpType($promoAction, $shipping);
        $data['target'] = $this->_getMailChimpTarget($promoAction, $shipping);
        switch ($data['type']) {
            case self::TYPE_PERCENTAGE:
                $data['amount'] = $rule->getDiscountAmount()/100;
                break;
            case self::TYPE_FIXED:
                if ($data['target']!=self::TARGET_SHIPPING) {
                    $data['amount'] = $rule->getDiscountAmount();
                } else {
                    $data['amount'] = 0;
                }
                break;
        }
        $data['enabled'] = (bool)$rule->getIsActive();
        if (!$data['target'] || !$data['type']) {
            return [];
        }

        return $data;
    }

    /**
     * @param $action
     * @return null|string
     */
    private function _getMailChimpType($action, $shipping)
    {
        $mailChimpType = null;
        if ($shipping==self::FREESHIPPING_NO) {
            switch ($action) {
                case \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION:
                    $mailChimpType = self::TYPE_PERCENTAGE;
                    break;
                case \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION:
                case \Magento\SalesRule\Model\Rule::CART_FIXED_ACTION:
                    $mailChimpType = self::TYPE_FIXED;
                    break;
            }
        } else {
            $mailChimpType = self::TYPE_FIXED;
        }
        return $mailChimpType;
    }

    /**
     * @param $action
     * @return null|string
     */
    private function _getMailChimpTarget($action, $shipping)
    {
        $mailChimpTarget = null;
        if ($shipping==self::FREESHIPPING_NO) {
            switch ($action) {
                case \Magento\SalesRule\Model\Rule::CART_FIXED_ACTION:
                case \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION:
                    $mailChimpTarget = self::TARGET_TOTAL;
                    break;
                case \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION:
                    $mailChimpTarget = self::TARGET_PER_ITEM;
                    break;
            }
        } else {
            $mailChimpTarget = self::TARGET_SHIPPING;
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
    protected function _updateSyncData($storeId, $entityId, $sync_delta = null, $sync_error = '', $sync_modified = 0)
    {
        $this->_helper->saveEcommerceData(
            $storeId,
            $entityId,
            \Ebizmarts\MailChimp\Helper\Data::IS_PROMO_RULE,
            $sync_delta,
            $sync_error,
            $sync_modified
        );
    }
}
