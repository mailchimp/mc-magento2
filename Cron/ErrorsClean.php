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
     * wants errors kept still gets them kept, up to here.
     *
     * The number is generous on purpose — the table exists to diagnose recent
     * failures, and a store erroring hard enough to reach this has a bigger
     * problem than the one this prevents.
     *
     * It bounds bytes too, but loosely, and the distinction is worth stating
     * rather than glossing. `type` and `errors` are TEXT, whose own ceiling is
     * 65,535 bytes, so a row cannot exceed roughly 128 KB and one store view
     * about 640 MB — per view, so a fifty-view install has a 250,000 row
     * ceiling, not 5,000. Against the ~110 bytes an error body measures in
     * practice. What this removes is the unbounded case, which was
     * the real problem; what it leaves is a constant that may or may not be
     * comfortable. If it ever proves not to be, the answer is to cap the
     * stored body rather than the row count.
     *
     * It bounds the store views the extension can see, and only those.
     * `store_id` carries no foreign key and getStores() returns neither deleted
     * views nor the admin store, so rows left behind by a removed store view
     * are visited by neither cleaner. That was already true of the age-based
     * clean; it is written here because this is the change that claims the
     * table is bounded, and for those rows it is not.
     */
    const MAX_ROWS_PER_STORE = 5000;

    /**
     * Rows the ceiling deletes per statement, and how many statements it may
     * issue in one run per store view.
     *
     * Its own limit rather than the age-based clean's. Each statement stays
     * bounded so it holds locks for milliseconds rather than for as long as the
     * table is large — the table this exists for is exactly the one where an
     * unbounded delete would be worst, and its cost there depends on row sizes,
     * replication and purge, none of which are knowable from here. Iterating
     * keeps every statement small and still converges within a single run.
     *
     * Larger than the age-based limit because each pass also runs the offset
     * query that finds the cut-off. That query is served by the
     * (store_id, id) index, but it is still a query per pass, so fewer and
     * bigger passes means fewer of them.
     *
     * Up to a million rows per store view per run — measured at about 16
     * seconds of work for that, with no single statement holding locks longer
     * than a third of a second. A table that has been growing for years is back
     * under the ceiling within a tick or two, not after weeks of hourly
     * nibbling. A store already under the ceiling costs one query and stops.
     */
    const OVERFLOW_LIMIT = 10000;
    const MAX_PASSES     = 100;

    /**
     * Seconds the whole job may spend across every store view in one run.
     *
     * A pass cap bounds work, not time, and how long a pass takes depends on
     * row sizes, replication and purge — the things this cannot know. That
     * matters because cron_groups.xml gives this group a schedule_lifetime of
     * two minutes and runs it in a single process: a job scheduled while this
     * is still going does not wait, it is marked missed. The sync and webhook
     * jobs are in that group and run every five minutes, so a long drain does
     * not delay them, it skips them.
     *
     * Well inside the two-minute window, so this job can never be the reason a
     * sync did not run. A table far past the ceiling then comes down over two
     * or three ticks instead of one, which is the cheaper mistake.
     *
     * It covers the age-based delete as well as the ceiling, deliberately. That
     * one filters on `date_add(added_at, ...)`, a function on the column, so it
     * cannot seek: on a store whose errors are all recent it walks the whole
     * partition to delete nothing. It has always done that, and it is the more
     * expensive half here. The sync it would starve does not care which half
     * spent the window.
     *
     * Spent in getStores() order, so while a drain is running the earlier views
     * are favoured and a later one can get nothing on a given tick. It still
     * converges, because the drain is finite and the next tick starts where
     * this one stopped.
     */
    const MAX_RUNTIME_SECONDS = 30;

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
    /**
     * Seam so the budget can be exercised without a test that waits for it.
     *
     * @return float
     */
    protected function now()
    {
        return microtime(true);
    }

    public function execute()
    {
        $deadline = $this->now() + self::MAX_RUNTIME_SECONDS;

        foreach ($this->storeManager->getStores() as $storeId => $val)
        {
            if ($this->now() >= $deadline) {
                // At the top, so the budget covers the whole job rather than
                // only the ceiling below it — the age-based delete is the more
                // expensive half on the tables this exists for, and a job that
                // overruns is one whose siblings get marked missed, whichever
                // half spent the window. Breaking rather than continuing also
                // stops walking the remaining views to do nothing.
                break;
            }

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
                $removed = 0;
                for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
                    if ($this->now() >= $deadline) {
                        // Out of time rather than out of work. The next tick
                        // picks up where this one stopped.
                        break;
                    }
                    $deleted = $this->chimpErrors->deleteOverflowByStore(
                        $storeId,
                        self::MAX_ROWS_PER_STORE,
                        self::OVERFLOW_LIMIT
                    );
                    if ($deleted < 1) {
                        break;
                    }
                    $removed += $deleted;
                }
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