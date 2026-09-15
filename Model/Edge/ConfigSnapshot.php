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

namespace Ebizmarts\MailChimp\Model\Edge;

use Ebizmarts\MailChimp\Helper\Data as MailChimpHelper;

/**
 * How this store view is configured, as flat scalars.
 *
 * The one artefact worth keeping from the support-log lane. For "this merchant
 * says X is not syncing" it answers more than anything else we hold, and the
 * lane that carries it today also carries request and response bodies -- which
 * means shopper emails and addresses -- so it cannot simply be left where it is.
 *
 * Flat, one key per setting, because the receiving whitelist takes a type and a
 * cap per key and has no path into a nested object. Nesting would arrive, be
 * accepted, and be dropped.
 *
 * Nothing here is merchant-authored text. Two settings are HTML the merchant
 * writes and they are reported as whether they were customised, not as their
 * content: the receiver truncates an over-length string rather than refusing
 * it, so real HTML would be stored as a fragment ending mid-tag -- something
 * that looks like content, is not, and reads as real later.
 */
class ConfigSnapshot
{
    /**
     * @var MailChimpHelper
     */
    private $helper;

    /**
     * @param MailChimpHelper $helper
     */
    public function __construct(MailChimpHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param  int $storeId
     * @return array
     */
    public function forStore($storeId)
    {
        $snapshot = [
            'cfg_active'                  => $this->flag(MailChimpHelper::XML_PATH_ACTIVE, $storeId),
            'cfg_store'                   => $this->text(MailChimpHelper::XML_MAILCHIMP_STORE, $storeId),
            'cfg_list'                    => $this->text(MailChimpHelper::XML_PATH_LIST, $storeId),
            'cfg_popup_form'              => $this->flag(MailChimpHelper::XML_POPUP_FORM, $storeId),
            'cfg_magento_email'           => $this->flag(MailChimpHelper::XML_MAGENTO_MAIL, $storeId),
            'cfg_two_way_sync'            => $this->flag(MailChimpHelper::XML_PATH_WEBHOOK_ACTIVE, $storeId),
            'cfg_enable_log'              => $this->flag(MailChimpHelper::XML_PATH_LOG, $storeId),
            // Read at the store view, unlike the lane this replaces. See below.
            'cfg_show_groups'             => $this->flag(MailChimpHelper::XML_INTEREST_IN_SUCCESS, $storeId),
            'cfg_group_description_set'   => $this->customised(MailChimpHelper::XML_INTEREST_SUCCESS_HTML_BEFORE, $storeId),
            'cfg_success_message_set'     => $this->customised(MailChimpHelper::XML_INTEREST_SUCCESS_HTML_AFTER, $storeId),
            'cfg_timeout'                 => $this->number(MailChimpHelper::XML_PATH_TIMEOUT, $storeId),
            'cfg_ecommerce'               => $this->flag(MailChimpHelper::XML_PATH_ECOMMERCE_ACTIVE, $storeId),
            'cfg_sync_all_customers'      => $this->flag(MailChimpHelper::XML_PATH_ALL_CUSTOMERS, $storeId),
            'cfg_subscribe_all_customers' => $this->flag(MailChimpHelper::XML_ECOMMERCE_OPTIN, $storeId),
            'cfg_first_date'              => $this->text(MailChimpHelper::XML_ECOMMERCE_FIRSTDATE, $storeId),
            'cfg_send_promo'              => $this->flag(MailChimpHelper::XML_SEND_PROMO, $storeId),
            'cfg_include_taxes'           => $this->flag(MailChimpHelper::XML_INCLUDING_TAXES, $storeId),
            'cfg_clean_error_months'      => $this->number(MailChimpHelper::XML_CLEAN_ERROR_MONTHS, $storeId),
            'cfg_ac'                      => $this->flag(MailChimpHelper::XML_ABANDONEDCART_ACTIVE, $storeId),
            'cfg_ac_first_date'           => $this->text(MailChimpHelper::XML_ABANDONEDCART_FIRSTDATE, $storeId),
            'cfg_ac_redirect_page'        => $this->text(MailChimpHelper::XML_ABANDONEDCART_PAGE, $storeId),
            'cfg_ac_save_email'           => $this->flag(MailChimpHelper::XML_ABANDONEDCART_EMAIL, $storeId),
        ];

        // Conditional exactly as the old lane had them: a popup url with no
        // popup, or a delete action with no webhook, describes nothing that is
        // in effect and would be read as if it were.
        if ($snapshot['cfg_popup_form']) {
            $snapshot['cfg_popup_url'] = $this->text(MailChimpHelper::XML_POPUP_URL, $storeId);
        }

        if ($snapshot['cfg_two_way_sync']) {
            // The raw value rather than the label the old lane composed. The
            // label table lives in the cron this replaces and would be a second
            // copy to keep correct; the receiver can name it once.
            //
            // Read at the store view, unlike the old lane. See below.
            $snapshot['cfg_delete_action'] = $this->text(MailChimpHelper::XML_PATH_WEBHOOK_DELETE, $storeId);
        }

        return $snapshot;
    }

    /**
     * A setting as a boolean.
     *
     * @param  string $path
     * @param  int    $storeId
     * @return bool
     */
    private function flag($path, $storeId)
    {
        return (bool)$this->helper->getConfigValue($path, $storeId);
    }

    /**
     * A setting as a string, absent rather than empty when unset.
     *
     * An empty string and an unset setting are the same thing to every consumer
     * of this, and sending it costs a key on every row of every install.
     *
     * @param  string $path
     * @param  int    $storeId
     * @return string|null
     */
    private function text($path, $storeId)
    {
        $value = $this->helper->getConfigValue($path, $storeId);

        return ($value === null || $value === '') ? null : (string)$value;
    }

    /**
     * @param  string $path
     * @param  int    $storeId
     * @return int|null
     */
    private function number($path, $storeId)
    {
        $value = $this->helper->getConfigValue($path, $storeId);

        return ($value === null || $value === '') ? null : (int)$value;
    }

    /**
     * Whether a merchant-authored field has anything in it.
     *
     * Never the content. See the class note.
     *
     * @param  string $path
     * @param  int    $storeId
     * @return bool
     */
    private function customised($path, $storeId)
    {
        $value = $this->helper->getConfigValue($path, $storeId);

        return $value !== null && trim((string)$value) !== '';
    }
}
