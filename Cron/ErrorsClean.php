<?php

/**
 * MailChimp Magento Component
 *
 * @category Ebizmarts
 * @package MailChimp
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 15/09/22 10:02 AM
 * @file: ErrorsClean.php
 */
namespace Ebizmarts\MailChimp\Cron;

class ErrorsClean
{
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $helper;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrors
     */
    protected $chimpErrors;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;
    const LIMIT = 1000;

    /**
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors
     * @param \Magento\Store\Model\StoreManager $storeManager
     */
    public function __construct(
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\MailChimpErrors $chimpErrors,
        \Magento\Store\Model\StoreManager $storeManager
    )
    {
        $this->helper = $helper;
        $this->chimpErrors = $chimpErrors;
        $this->storeManager = $storeManager;
    }
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $storeId => $val)
        {
            $period = $this->helper->getConfigValue(\Ebizmarts\MailChimp\Helper\Data::XML_CLEAN_ERROR_MONTHS, $storeId);
            if ($period > 0) {
                try {
                    $this->helper->log("Cleaning errors for store [$storeId] older than $period months");
                    $this->chimpErrors->deleteByStorePeriod($storeId,$period,self::LIMIT);
                } catch (\Exception $e) {
                    $this->helper->log($e->getMessage());
                }
            }
        }
    }
}