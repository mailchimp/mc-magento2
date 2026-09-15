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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Api;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Api\Result;
use PHPUnit\Framework\TestCase;

/**
 * When a batch has finished on Mailchimp and its result cannot be read.
 *
 * The distinction these pin is the one nobody could see before: a batch still
 * being processed and a batch whose result is unreadable produced the same
 * outcome, so the second was invisible and any count of attempts would have
 * counted the first.
 */
class BatchGiveUpTest extends TestCase
{
    /** @var array */
    private $marked = [];

    /** @var array */
    private $logged = [];

    /**
     * A batch row that behaves like the model: magic getters and setters over
     * data, and a resource whose save() the code calls directly.
     *
     * @param  int $attempts
     * @return object
     */
    private function batchRow($attempts)
    {
        return new class($attempts) {
            public $data;
            public $saves = 0;
            public function __construct($attempts)
            {
                $this->data = ['batch_id' => 'abc123', 'status' => 'pending', 'response_attempts' => $attempts];
            }
            public function getBatchId() { return $this->data['batch_id']; }
            public function getStatus() { return $this->data['status']; }
            public function getResponseAttempts() { return $this->data['response_attempts']; }
            public function setStatus($v) { $this->data['status'] = $v; return $this; }
            public function setResponseAttempts($v) { $this->data['response_attempts'] = $v; return $this; }
            public function getResource()
            {
                return new class($this) {
                    private $row;
                    public function __construct($row) { $this->row = $row; }
                    public function save($item) { $this->row->saves++; }
                };
            }
        };
    }

    /**
     * @return Result
     */
    private function resultModel()
    {
        $this->marked = [];
        $this->logged = [];

        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $test = $this;

        $syncHelper = new class($test) {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function markAllAsModifiedByBatchId($batchId) { $this->test->recordMark($batchId); }
            public function deleteAllByBatchId($batchId) { $this->test->recordDelete($batchId); }
        };

        $helper = new class($test) {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function log($message) { $this->test->recordLog($message); }
        };

        foreach (['syncHelper' => $syncHelper, '_helper' => $helper] as $name => $value) {
            $property = new \ReflectionProperty(Result::class, $name);
            $property->setAccessible(true);
            $property->setValue($result, $value);
        }

        return $result;
    }

    public function recordMark($batchId) { $this->marked[] = ['mark', $batchId]; }

    public function recordDelete($batchId) { $this->marked[] = ['delete', $batchId]; }

    public function recordLog($message) { $this->logged[] = $message; }

    /**
     * @param  Result $result
     * @param  object $row
     * @return bool
     */
    private function giveUp($result, $row)
    {
        $method = new \ReflectionMethod(Result::class, 'giveUpOnResponse');
        $method->setAccessible(true);

        return $method->invoke($result, $row);
    }

    public function testAnEarlyFailureIsCountedAndTheBatchIsKept()
    {
        $result = $this->resultModel();
        $row = $this->batchRow(0);

        $this->assertFalse($this->giveUp($result, $row));
        $this->assertSame(1, $row->getResponseAttempts());
        $this->assertSame('pending', $row->getStatus());
        $this->assertSame([], $this->marked, 'nothing should be re-sent while the batch is still being retried');
    }

    /**
     * Four failures leave it alone; the fifth is the one that stops.
     */
    public function testTheCapIsTheFifthAttempt()
    {
        $result = $this->resultModel();
        $row = $this->batchRow(0);

        for ($attempt = 1; $attempt < Result::MAX_RESPONSE_ATTEMPTS; $attempt++) {
            $this->assertFalse($this->giveUp($result, $row), "attempt $attempt should not give up");
            $this->assertSame('pending', $row->getStatus());
        }

        $this->assertTrue($this->giveUp($result, $row));
        $this->assertSame(Result::MAX_RESPONSE_ATTEMPTS, $row->getResponseAttempts());
        $this->assertSame(MailChimpHelper::BATCH_ERROR, $row->getStatus());
    }

    /**
     * The operations already ran, so what the batch carried has to go again --
     * and it is marked rather than deleted, because the rows are what the rest
     * of the extension reads to know an entity exists at all.
     */
    public function testGivingUpQueuesEverythingTheBatchCarried()
    {
        $result = $this->resultModel();
        $this->giveUp($result, $this->batchRow(Result::MAX_RESPONSE_ATTEMPTS - 1));

        $this->assertSame([['mark', 'abc123']], $this->marked);
    }

    /**
     * The count is in the line because the number is the only thing that says
     * whether this was a run of bad luck or a result nobody was ever going to
     * be able to read.
     */
    public function testGivingUpSaysSoWithTheCount()
    {
        $result = $this->resultModel();
        $this->giveUp($result, $this->batchRow(Result::MAX_RESPONSE_ATTEMPTS - 1));

        $this->assertCount(1, $this->logged);
        $this->assertStringContainsString('abc123', $this->logged[0]);
        $this->assertStringContainsString((string)Result::MAX_RESPONSE_ATTEMPTS, $this->logged[0]);
    }

    /**
     * Every attempt is written down, including the ones that do not give up.
     * A count kept only in memory would restart on every cron run and the cap
     * would never be reached.
     */
    public function testEveryAttemptIsPersisted()
    {
        $result = $this->resultModel();
        $row = $this->batchRow(0);

        $this->giveUp($result, $row);
        $this->assertSame(1, $row->saves);
    }
}
