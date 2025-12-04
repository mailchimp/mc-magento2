/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Ebizmarts
 * @package     Ebizmarts_MailChimp
 * @author      Ebizmarts Team <info@ebizmarts.com>
 * @copyright   Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'jquery',
        'mage/cookies'
    ],
    function ($) {
        "use strict";

        $.widget('mage.mailchimpregistration', {
            "options": {
                "checkCampaignUrl": ""
            },

            _init: function () {
                var self = this;
                $('#is_subscribed').change(function () {
                    var subscribed =$('#is_subscribed').is(':checked');
                    if (subscribed) {
                        $('#mailchimp_groups').show()
                    } else {
                        $('#mailchimp_groups').hide()
                    }
                });
            }
        });
        return $.mage.mailchimpregistration;
    }
);
