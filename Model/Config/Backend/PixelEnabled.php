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

namespace Ebizmarts\MailChimp\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Keeps an untouched "No" from looking like a decision.
 *
 * The Pixel toggle is store-view only (`showInDefault="0" showInWebsite="0"`),
 * and Magento renders the inherit checkbox only for fields that are visible at
 * a wider scope -- `Block/System/Config/Form.php:378-379` passes the field's own
 * `showInDefault()` / `showInWebsite()` into the predicates that decide it. With
 * no checkbox there is no `inherit` to post, so every Save Config at store-view
 * scope writes a row for this field, including on the save that connects the
 * store view in the first place.
 *
 * That matters because the Pixel is turned on when a store view connects, and
 * the one thing that must not be overridden is a merchant who said No. If an
 * untouched save writes `0`, there is no way to tell that No from "nobody has
 * been asked yet" -- and the merchants most likely to write it are the ones
 * connecting at store-view scope, which is the normal multi-store setup.
 *
 * So the save is suppressed when both of these hold:
 *
 *   - no row exists for this field at this scope. Magento sets `config_id` from
 *     the existing row and unsets it when there is none
 *     (`Magento\Config\Model\Config::_processGroup()`, :461-471), so this is
 *     known here without a query of our own;
 *   - the posted value is what the scope already resolves to, which is what
 *     `isValueChanged()` compares against.
 *
 * Afterwards a `0` row means someone changed the field, which is the property
 * the connection hook relies on. Suppressing in `beforeSave()` works because
 * `AbstractDb::save()` calls `beforeSave()` and then checks `isSaveAllowed()`
 * (:390-391).
 */
class PixelEnabled extends Value
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        if (!$this->getConfigId() && !$this->isValueChanged()) {
            $this->_dataSaveAllowed = false;
        }

        return $this;
    }
}
