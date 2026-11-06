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

namespace Ebizmarts\MailChimp\Test\Unit\Model\Config\Backend;

use Ebizmarts\MailChimp\Model\Config\Backend\PixelEnabled;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\TestCase;

/**
 * What separates a merchant's "No" from nobody having been asked.
 *
 * The Pixel toggle renders no inherit checkbox -- it is store-view only, and
 * Magento offers the checkbox only on fields visible at a wider scope -- so
 * every Save Config at store-view scope posts this field and would write a row
 * for it. Since the Pixel is turned on when a store view connects and an
 * explicit No must never be overridden, a row that nobody chose is exactly the
 * thing that must not exist.
 */
class PixelEnabledTest extends TestCase
{
    /**
     * The backend model with the two inputs the decision reads, and nothing
     * else: Value's own constructor wants half the framework and none of it is
     * under test.
     *
     * @param  string|null $configId  the row id Magento attaches when one exists
     * @param  string      $posted
     * @param  string      $resolved  what the scope resolves to today
     * @return PixelEnabled
     */
    private function backend($configId, string $posted, string $resolved): PixelEnabled
    {
        $backend = new class ($resolved) extends PixelEnabled {
            /** @var string */
            private $resolved;

            public function __construct($resolved)
            {
                $this->resolved = $resolved;
            }

            public function getOldValue()
            {
                return $this->resolved;
            }
        };

        $events = new \ReflectionProperty(AbstractModel::class, '_eventManager');
        $events->setAccessible(true);
        $events->setValue($backend, $this->createMock(ManagerInterface::class));

        $backend->setValue($posted);

        if ($configId !== null) {
            $backend->setConfigId($configId);
        }

        return $backend;
    }

    /**
     * The case this class exists for: a section save at store-view scope that
     * posted the field without anyone touching it.
     */
    public function testAnUntouchedNoIsNotWritten(): void
    {
        $backend = $this->backend(null, '0', '0');

        $backend->beforeSave();

        $this->assertFalse($backend->isSaveAllowed(), 'an untouched No must leave no row behind');
    }

    public function testTurningThePixelOnIsWritten(): void
    {
        $backend = $this->backend(null, '1', '0');

        $backend->beforeSave();

        $this->assertTrue($backend->isSaveAllowed());
    }

    /**
     * A merchant who turned the Pixel off has a row already. Re-saving the
     * section posts the same value, which is unchanged -- but the row is theirs
     * and must survive, so the suppression must not apply here.
     */
    public function testAnExistingRowIsKeptEvenWhenTheValueIsUnchanged(): void
    {
        $backend = $this->backend('417', '0', '0');

        $backend->beforeSave();

        $this->assertTrue($backend->isSaveAllowed());
    }

    public function testTurningThePixelBackOffIsWritten(): void
    {
        $backend = $this->backend('417', '0', '1');

        $backend->beforeSave();

        $this->assertTrue($backend->isSaveAllowed());
    }
}
