<?php
/**
 * Ebizmarts_MailChimp
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Ebizmarts\MailChimp\Test\Unit\Model\Logger;

use Ebizmarts\MailChimp\Model\Logger\Logger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    /**
     * @var TestHandler
     */
    private $handler;

    /**
     * @var Logger
     */
    private $logger;

    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        $this->logger = new Logger('mailchimp', [$this->handler]);
    }

    public function testStringMessageIsLoggedAfterAnUnrelatedJsonDecodeFailure()
    {
        json_decode('<html>502 Bad Gateway</html>');
        $this->assertNotSame(JSON_ERROR_NONE, json_last_error());

        $this->logger->mailchimpLog('plain failure message', null);

        $this->assertSame("plain failure message\r\n", $this->loggedMessage());
    }

    public function testMessageThatCannotBeEncodedIsReplacedByTheEncoderError()
    {
        $this->logger->mailchimpLog(['value' => NAN], null);

        $this->assertSame("Inf and NaN cannot be JSON encoded\r\n", $this->loggedMessage());
    }

    private function loggedMessage()
    {
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        $record = $records[0];

        return is_array($record) ? $record['message'] : $record->message;
    }
}
