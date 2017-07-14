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

        $.widget('mage.campaigncatcher', {
            _init: function () {
                $(document).ready(function () {
                    var urlparams = location.search.substr(1).split('&');
                    var params = new Array();
                    var mc_cid = null;
                    var isMailchimp = false;
                    for (var i = 0; i < urlparams.length; i++) {
                        var param = urlparams[i].split('=');
                        var key = param[0];
                        var val = param[1];
                        if (key && val) {
                            params[key] = val;
                        }

                        if (key=='utm_source') {
                            var reg = /^mailchimp$/;
                            if (reg.exec(val)) {
                                isMailchimp = true;
                            }
                        } else {
                            if (key=='mc_cid') {
                                mc_cid = val;
                            }
                        }
                    }
                    if (mc_cid && !isMailchimp) {
                        $.mage.cookies.set('mailchimp_campaign_id' , mc_cid);
                        $.mage.cookies.set('mailchimp_landing_page', location);
                    }

                    if (isMailchimp) {
                        $.mage.cookies.clear('mailchimp_campaign_id');
                        $.mage.cookies.set('mailchimp_landing_page', location);
                    }
                });
            }
        });
        return $.mage.campaigncatcher;
    }
);