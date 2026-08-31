<?php
/**
 * Ebizmarts_MailChimp Magento Component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Test\Unit\Cron;

use Ebizmarts\MailChimp\Cron\ErrorsClean;
use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\MailChimpErrors;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

class ErrorsCleanTest extends TestCase
{
    /**
     * @param  mixed $period value of clean_errors_months
     * @return array [$cron, $errors]
     */
    private function make($period, array $storeIds = [1])
    {
        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')->willReturn($period);

        $errors = $this->createMock(MailChimpErrors::class);

        $storeManager = $this->createMock(StoreManager::class);
        // getStores() is keyed by store id, and the cron reads the key.
        $storeManager->method('getStores')->willReturn(array_fill_keys($storeIds, new \stdClass()));

        return [new ErrorsClean($helper, $errors, $storeManager), $errors];
    }

    /**
     * The ceiling is a safety property, not a preference. A store that has the
     * age-based clean switched off — which is every store by default, since
     * the field has no default and saving the section stores a 0 — is exactly
     * the store that needs the table bounded.
     */
    public function testTheCeilingIsAppliedEvenWithTheAgeCleanSwitchedOff(): void
    {
        list($cron, $errors) = $this->make(0);

        $errors->expects($this->never())->method('deleteByStorePeriod');
        $errors->expects($this->once())
            ->method('deleteOverflowByStore')
            ->with(1, ErrorsClean::MAX_ROWS_PER_STORE, ErrorsClean::OVERFLOW_LIMIT)
            ->willReturn(0);

        $cron->execute();
    }

    public function testBothRunWhenTheMerchantAsksForAnAgeBasedClean(): void
    {
        list($cron, $errors) = $this->make(3);

        $errors->expects($this->once())->method('deleteByStorePeriod')->with(1, 3, ErrorsClean::LIMIT);
        $errors->expects($this->once())->method('deleteOverflowByStore')->willReturn(0);

        $cron->execute();
    }

    /**
     * The two are independent: a failing age-based clean must not take the
     * ceiling with it, or the table stops being bounded for the store whose
     * cleanup is broken.
     */
    public function testAFailingAgeCleanDoesNotSkipTheCeiling(): void
    {
        list($cron, $errors) = $this->make(3);

        $errors->method('deleteByStorePeriod')->willThrowException(new \Exception('table is locked'));
        $errors->expects($this->once())->method('deleteOverflowByStore')->willReturn(0);

        $cron->execute();
    }

    /**
     * The ceiling is per store view, so every view has to be offered it — one
     * view failing must not stop the rest.
     */
    public function testEveryStoreViewIsBounded(): void
    {
        list($cron, $errors) = $this->make(0, [1, 2, 3]);

        $seen = [];
        $errors->method('deleteOverflowByStore')->willReturnCallback(
            function ($storeId) use (&$seen) {
                $seen[] = $storeId;
                if ($storeId === 2) {
                    throw new \Exception('deadlock');
                }
                return 0;
            }
        );

        $cron->execute();

        $this->assertSame([1, 2, 3], $seen);
    }

    /**
     * A run keeps going until the store is under the ceiling. Deleting a fixed
     * slice per run and stopping would leave a table that has been growing for
     * years to come down over weeks, which is the case the ceiling exists for.
     */
    public function testTheCeilingKeepsGoingUntilThereIsNothingLeftToDelete(): void
    {
        list($cron, $errors) = $this->make(0);

        $remaining = 3;
        $errors->expects($this->exactly(4))
            ->method('deleteOverflowByStore')
            ->willReturnCallback(
                function () use (&$remaining) {
                    return $remaining-- > 0 ? ErrorsClean::OVERFLOW_LIMIT : 0;
                }
            );

        $cron->execute();
    }

    /**
     * But one run is still bounded. Without a cap a store far enough past the
     * ceiling would hold the cron for as long as it took, and the whole reason
     * the delete is chunked is to keep any single run predictable.
     */
    public function testOneRunIsBoundedEvenIfTheStoreNeverComesDown(): void
    {
        list($cron, $errors) = $this->make(0);

        $errors->expects($this->exactly(ErrorsClean::MAX_PASSES))
            ->method('deleteOverflowByStore')
            ->willReturn(ErrorsClean::OVERFLOW_LIMIT);

        $cron->execute();
    }


    /**
     * A pass caps work, not time, and how long a pass takes depends on things
     * this cannot know. The cron group gives this job a two-minute window and
     * runs it in one process alongside the sync jobs, so a drain that overruns
     * does not delay them — Magento marks them missed. The ceiling stops on the
     * clock, and the next tick continues.
     */
    public function testTheCeilingStopsOnTheClockNotOnlyOnThePassCap(): void
    {
        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')->willReturn(0);
        $errors = $this->createMock(MailChimpErrors::class);
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn([1 => new \stdClass()]);

        // A clock that jumps a quarter of the budget per reading. One reading
        // sets the deadline, one guards the store loop, then the third pass
        // lands on it. Anything counting passes alone would run 100.
        $step = ErrorsClean::MAX_RUNTIME_SECONDS / 4;
        $cron = new class ($helper, $errors, $storeManager, $step) extends ErrorsClean {
            private $tick = 0;
            private $step;

            public function __construct($helper, $errors, $storeManager, $step)
            {
                parent::__construct($helper, $errors, $storeManager);
                $this->step = $step;
            }

            protected function now()
            {
                return $this->tick++ * $this->step;
            }
        };

        $errors->expects($this->exactly(2))
            ->method('deleteOverflowByStore')
            ->willReturn(ErrorsClean::OVERFLOW_LIMIT);

        $cron->execute();
    }


    /**
     * The budget has to cover the whole job, not the ceiling alone. The
     * age-based delete runs first for each store view and is the more
     * expensive half — it filters on a function of the column, so it cannot
     * seek and walks the partition even when it deletes nothing. A sync marked
     * missed does not care which half spent the window.
     */
    public function testTheBudgetCoversTheAgeBasedCleanToo(): void
    {
        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')->willReturn(3);
        $errors = $this->createMock(MailChimpErrors::class);
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn(array_fill_keys([1, 2, 3, 4, 5], new \stdClass()));

        // Already past the deadline on the very first reading.
        $cron = new class ($helper, $errors, $storeManager) extends ErrorsClean {
            private $tick = 0;

            protected function now()
            {
                return $this->tick++ === 0 ? 0 : ErrorsClean::MAX_RUNTIME_SECONDS + 1;
            }
        };

        $errors->expects($this->never())->method('deleteByStorePeriod');
        $errors->expects($this->never())->method('deleteOverflowByStore');

        $cron->execute();
    }

    /**
     * And it stops the loop rather than spinning through the remaining views
     * to do nothing with each of them.
     */
    public function testAnExhaustedBudgetStopsTheLoopInsteadOfSkippingEachView(): void
    {
        $helper = $this->createMock(MailChimpHelper::class);
        $reads = 0;
        $helper->method('getConfigValue')->willReturnCallback(
            function () use (&$reads) {
                $reads++;
                return 0;
            }
        );
        $errors = $this->createMock(MailChimpErrors::class);
        $errors->method('deleteOverflowByStore')->willReturn(0);
        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStores')->willReturn(array_fill_keys(range(1, 20), new \stdClass()));

        // Readings: deadline, the first view's guard, then the first view's
        // pass loop is already past it. The second view's guard breaks the
        // loop, so exactly one view is ever visited.
        $cron = new class ($helper, $errors, $storeManager) extends ErrorsClean {
            private $tick = 0;

            protected function now()
            {
                return $this->tick++ < 2 ? 0 : ErrorsClean::MAX_RUNTIME_SECONDS + 1;
            }
        };

        $cron->execute();

        $this->assertSame(1, $reads, 'the loop kept visiting store views after the budget ran out');
    }


    /**
     * The ceiling runs before the age-based clean, not after.
     *
     * The ceiling is the safety property and the age clean is a preference, so
     * the safety property draws on the budget first. It also means every row
     * the ceiling removes is a row the age clean — which cannot seek — does not
     * have to scan, and that the preference can never starve the ceiling of
     * passes on a store where it matches nothing.
     */
    public function testTheCeilingRunsBeforeTheAgeBasedClean(): void
    {
        list($cron, $errors) = $this->make(3);

        $orden = [];
        $errors->method('deleteOverflowByStore')->willReturnCallback(
            function () use (&$orden) {
                $orden[] = 'ceiling';
                return 0;
            }
        );
        $errors->method('deleteByStorePeriod')->willReturnCallback(
            function () use (&$orden) {
                $orden[] = 'age';
            }
        );

        $cron->execute();

        $this->assertSame(['ceiling', 'age'], $orden);
    }

}
