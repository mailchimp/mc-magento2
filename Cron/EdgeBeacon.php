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

namespace Ebizmarts\MailChimp\Cron;

use Ebizmarts\MailChimp\Model\Edge\Beacon;

/**
 * Hourly entry point for the status reporting beacon.
 */
class EdgeBeacon
{
    /**
     * @var Beacon
     */
    private $beacon;

    /**
     * @param Beacon $beacon
     */
    public function __construct(
        Beacon $beacon
    ) {
        $this->beacon = $beacon;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->beacon->execute();
    }
}
