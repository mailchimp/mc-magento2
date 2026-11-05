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

namespace Ebizmarts\MailChimp\Model\Service\Pixel;

/**
 * Store views that connected during this request and still need their Pixel.
 *
 * The save that connects a store view knows that it did, but it cannot turn
 * the Pixel on itself. Backend models run inside `Config::save()`'s
 * transaction, before Magento re-initialises the configuration
 * (`module-config/Model/Config.php`, `$saveTransaction->save()` and then
 * `$this->_appConfig->reinit()`). Every read through the scope config at that
 * point still answers with the values from before the save -- including the
 * API key, which on a first connection arrives in the same Save Config as the
 * store. Provisioning from there talks to Mailchimp with the old key, or with
 * none.
 *
 * So the backend model only records the store view here, and
 * `Observer\ConfigObserver` provisions it on
 * `admin_system_config_changed_section_mailchimp`, which Magento dispatches
 * after the save is committed and the configuration re-initialised.
 *
 * Shared by default in the object manager, so the backend model and the
 * observer see the same instance within one request.
 */
class NewConnectionQueue
{
    /**
     * @var int[]  store view ids, keyed by themselves so a repeat is a no-op
     */
    private $storeIds = [];

    /**
     * @param  int $storeId
     * @return void
     */
    public function add(int $storeId): void
    {
        $this->storeIds[$storeId] = $storeId;
    }

    /**
     * Return the queued store view ids and empty the queue, so that one
     * connection provisions once.
     *
     * @return int[]
     */
    public function drain(): array
    {
        $storeIds = array_values($this->storeIds);
        $this->storeIds = [];

        return $storeIds;
    }
}
