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
     * Rows kept per store view, whatever the merchant asked for.
     *
     * The age-based clean below answers "how long do I want errors kept?", and
     * keeping them forever is a legitimate answer — it is also the answer every
     * install carries by default, since the field has no default and saving the
     * configuration section stores a 0. So on its own it bounds nothing.
     *
     * This bounds it regardless, without overriding anyone: a merchant who
     * wants errors kept still gets them kept, up to here. The number is
     * generous on purpose — the table exists to diagnose recent failures, and
     * a store erroring hard enough to reach this has a bigger problem than the
     * one this prevents.
     *
     * It bounds bytes too, but loosely, and the distinction is worth stating
     * rather than glossing. `type` and `errors` are TEXT, whose own ceiling is
     * 65,535 bytes, so a store view cannot exceed roughly 128 KB per row —
     * about 640 MB here, against the ~110 bytes an error body actually
     * measures in practice. What this removes is the unbounded case, which was
     * the real problem; what it leaves is a constant that may or may not be
     * comfortable. If it ever proves not to be, the answer is to cap the
     * stored body rather than the row count.
     */
    const MAX_ROWS_PER_STORE = 5000;

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

            // Deliberately outside the check above. The age-based clean is a
            // preference and can legitimately be switched off; the ceiling is
            // not, and a store that has switched the preference off is exactly
            // the store that needs it.
            try {
                $removed = $this->chimpErrors->deleteOverflowByStore(
                    $storeId,
                    self::MAX_ROWS_PER_STORE,
                    self::LIMIT
                );
                if ($removed > 0) {
                    $this->helper->log(
                        "Store [$storeId] held more than " . self::MAX_ROWS_PER_STORE
                        . " errors, removed $removed of the oldest"
                    );
                }
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
            }
        }
    }
}