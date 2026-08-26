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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;
use Ebizmarts\MailChimp\Model\Edge\LivenessSignals;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class LivenessSignalsTest extends TestCase
{
    /**
     * Columns handed to Select::from across the whole call, so a test can
     * assert that a forbidden one is never requested.
     *
     * @var array
     */
    private $selectedColumns = [];

    /**
     * @param  string|false $syncDelta
     * @param  array|false  $errorRow
     * @param  string       $mailchimpStoreId
     * @return LivenessSignals
     */
    private function makeSignals($syncDelta, $errorRow, $mailchimpStoreId = 'mc-store-1')
    {
        $this->selectedColumns = [];

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnCallback(
            function ($table, $columns = '*') use ($select) {
                foreach ((array)$columns as $column) {
                    $this->selectedColumns[] = $column;
                }
                return $select;
            }
        );
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $select->method('limit')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn($syncDelta);
        $connection->method('fetchRow')->willReturn($errorRow);

        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $helper = $this->createMock(MailChimpHelper::class);
        $helper->method('getConfigValue')->willReturn($mailchimpStoreId);

        return new LivenessSignals($resource, $helper);
    }

    /**
     * The service deletes a field on an explicit null and keeps it when the key is
     * absent, so a recovered store must actively send null rather than omit.
     */
    public function testErrorFieldsAreAlwaysPresentSoRecoveryClearsThem(): void
    {
        $signals = $this->makeSignals('2026-08-24 12:00:00', false)->forStore(1);

        $this->assertArrayHasKey('last_error_type', $signals);
        $this->assertArrayHasKey('last_error_status', $signals);
        $this->assertArrayHasKey('last_error_at', $signals);
        $this->assertNull($signals['last_error_type']);
        $this->assertNull($signals['last_error_status']);
        $this->assertNull($signals['last_error_at']);
    }

    public function testErrorFieldsCarryTheNewestRow(): void
    {
        $row = ['type' => 'ecommerce', 'status' => '404', 'added_at' => '2026-08-24 11:00:00'];

        $signals = $this->makeSignals('2026-08-24 12:00:00', $row)->forStore(1);

        $this->assertSame('ecommerce', $signals['last_error_type']);
        $this->assertSame('404', $signals['last_error_status']);
        $this->assertSame('2026-08-24 11:00:00', $signals['last_error_at']);
    }

    /**
     * Omitted rather than null: a transient local read problem must not erase a
     * good value the service already holds.
     */
    public function testLastSyncIsOmittedWhenUnknown(): void
    {
        $signals = $this->makeSignals(false, false)->forStore(1);

        $this->assertArrayNotHasKey('last_sync_at', $signals);
    }

    public function testLastSyncIsSentWhenKnown(): void
    {
        $signals = $this->makeSignals('2026-08-24 12:00:00', false)->forStore(1);

        $this->assertSame('2026-08-24 12:00:00', $signals['last_sync_at']);
    }

    public function testNoSyncDeltaIsReadWithoutAMailchimpStore(): void
    {
        $signals = $this->makeSignals('2026-08-24 12:00:00', false, '')->forStore(1);

        $this->assertArrayNotHasKey('last_sync_at', $signals);
    }

    /**
     * mailchimp_errors.errors holds the raw Mailchimp body and can carry
     * customer details. It must never be selected.
     */
    public function testTheShopperBearingColumnIsNeverSelected(): void
    {
        $this->makeSignals('2026-08-24 12:00:00', ['type' => 'x', 'status' => '1', 'added_at' => 'y'])
            ->forStore(1);

        $this->assertNotEmpty($this->selectedColumns);
        $this->assertNotContains('errors', $this->selectedColumns);
        $this->assertNotContains('notification_data', $this->selectedColumns);
        $this->assertNotContains('*', $this->selectedColumns);
    }
}
