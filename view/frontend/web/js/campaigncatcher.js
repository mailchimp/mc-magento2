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
        'jquery'
    ],
    function ($) {
        "use strict";

        $.widget('mage.campaigncatcher', {
            "options": {
                "checkCampaignUrl": ""
            },

            _init: function () {
                var self = this;
                $(document).ready(function () {
                    var path = location;
                    var urlparams = null;
                    var isGet = path.search.search('&');
                    var mc_cid = null;
                    var isMailchimp = false;
                    var checkCampaignUrl = self.options.checkCampaignUrl;
                    if(isGet > 0) {
                        urlparams = path.search.substr(1).split('&');
                        for (var i = 0; i < urlparams.length; i++) {
                            var param = urlparams[i].split('=');
                            var key = param[0];
                            var val = param[1];

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
                    } else {
                        urlparams = path.pathname.split('/');
                        var utmIndex = $.inArray('utm_source', urlparams);
                        var mccidIndex = $.inArray('mc_cid', urlparams);
                        if (utmIndex != -1) {
                            var value = urlparams[utmIndex + 1];
                            var reg = /^mailchimp$/;
                            if (reg.exec(value)) {
                                isMailchimp = true;
                            }
                        } else {
                            if (mccidIndex != -1) {
                                mc_cid = urlparams[mccidIndex + 1];
                            }
                        }
                    }
                    if (mc_cid && !isMailchimp) {
                        $.ajax({
                            url: checkCampaignUrl,
                            data: {'form_key': window.FORM_KEY,'mc_cid': mc_cid},
                            type: 'GET',
                            dataType: 'json',
                            showLoader: false
                        }).done(function (data) {
                            if (data.valid==0) {
                                $.mage.cookies.clear('mailchimp_campaign_id');
                                $.mage.cookies.set('mailchimp_landing_page', location);
                            } else if (data.valid==1) {
                                $.mage.cookies.set('mailchimp_campaign_id' , mc_cid);
                                $.mage.cookies.set('mailchimp_landing_page', location);
                            }
                        });
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