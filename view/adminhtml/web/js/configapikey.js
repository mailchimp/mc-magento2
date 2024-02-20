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
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/confirm'
    ],
    function ($, alert, confirmation) {
        "use strict";

        $.widget('mage.configmonkeyapikey', {
            "options": {
                "storeUrl": "",
                "detailsUrl": "",
                "storeGridUrl": "",
                "createWebhookUrl": "",
                "getInterestUrl": "",
                "resyncSubscribersUrl": "",
                "resyncProductsUrl": "",
                "cleanEcommerceUrl": "",
                "checkEcommerceUrl": "",
                "fixMailchimpjsUrl": "",
                "scope": "",
                "scopeId": ""
            },

            _init: function () {
                var self = this;
                $('#mailchimp_general_apikey').change(function () {
                    var apiKey = $('#mailchimp_general_apikey').val();
                    self._loadStores(apiKey);
                });
                $('#mailchimp_general_monkeystore').change(function () {
                    self._loadDetails();
                    // self._loadInterest();
                });
                $('#row_mailchimp_general_monkeystore').find('.note').append(' <a href="' + self.options.storeGridUrl + '">here</a>');
                if ($('#mailchimp_general_monkeystore option').length > 1) {
                    $('#row_mailchimp_general_monkeystore .note').hide();
                }
                $('#mailchimp_general_webhook_create').click(function () {
                    var apiKey = $('#mailchimp_general_apikey').val();
                    var listId = $('#mailchimp_general_monkeylist').find(':selected').val();
                    self._createWebhook(apiKey, listId);
                });
                $('#mailchimp_general_resync_subscribers').click(function () {
                    var listId = $('#mailchimp_general_monkeylist').find(':selected').val();
                    self._resyncSubscribers(listId);
                });
                $('#mailchimp_ecommerce_resync_products').click(function () {
                    var mailchimpStoreId = $('#mailchimp_general_monkeystore').find(':selected').val();
                    self._resyncProducts(mailchimpStoreId);
                });
                self._checkCleanButton();
                $('#mailchimp_general_clean_ecommerce').click(function () {
                    self._cleanEcommerce();
                });
                $('#mailchimp_general_fix_mailchimpjs').click(function () {
                    self._fixMailchimpJS();
                });
                $('#mailchimp_ecommerce_active').change(function () {
                    var ecommerceEnabled = $('#mailchimp_ecommerce_active').find(':selected').val();
                    var abandonedCartEnabled = $('#mailchimp_abandonedcart_active').find(':selected').val();
                    if (ecommerceEnabled == 0 && abandonedCartEnabled == 1) {
                        self._changeEcommerce();
                    }
                });
                $('#mailchimp_abandonedcart_active').change(function () {
                    var ecommerceEnabled = $('#mailchimp_ecommerce_active').find(':selected').val();
                    var abandonedCartEnabled = $('#mailchimp_abandonedcart_active').find(':selected').val();
                    if (ecommerceEnabled == 0 && abandonedCartEnabled == 1) {
                        self._changeAbandonedCart();
                    }
                });
                var ecommerceEnabled = $('#mailchimp_ecommerce_active').find(':selected').val();
                var abandonedCartEnabled = $('#mailchimp_abandonedcart_active').find(':selected').val();
                if (ecommerceEnabled == 0 && abandonedCartEnabled == 1) {
                    self._changeAbandonedCart();
                }
                $('#mailchimp_ecommerce_campaign_action').attr('size',3);

            },
            _changeEcommerce: function () {
                var self = this;
                confirmation( {
                        content: "If you disable Ecommerce, we will disable Abandoned Cart",
                        actions: {
                            confirm: function () {
                                var tag = '#mailchimp_abandonedcart_active'
                                $(tag).empty();
                                $(tag).append($('<option>', {
                                    value: "0",
                                    text: 'No',
                                    selected: "selected"
                                }));
                                $(tag).append($('<option>', {
                                    value: "1",
                                    text: 'Yes'
                                }));
                                self._hideAbandonedCart();
                            },
                            cancel: function () {
                                var tag = '#mailchimp_ecommerce_active'
                                $(tag).empty();
                                $(tag).append($('<option>', {
                                    value: "0",
                                    text: 'No',
                                }));
                                $(tag).append($('<option>', {
                                    value: "1",
                                    text: 'Yes',
                                    selected: "selected"
                                }));
                                self._showEcommerce();
                            }
                        }
                    }
                );
            },
            _changeAbandonedCart: function () {
                var self = this;
                confirmation( {
                        content: "If you enable Abandoned Cart we need to enable Ecommerce",
                        actions: {
                            confirm: function () {
                                var tag = '#mailchimp_ecommerce_active'
                                $(tag).empty();
                                $(tag).append($('<option>', {
                                    value: "0",
                                    text: 'No'
                                }));
                                $(tag).append($('<option>', {
                                    value: "1",
                                    text: 'Yes',
                                    selected: "selected"
                                }));
                                self._showEcommerce();
                            },
                            cancel: function () {
                                var tag = '#mailchimp_abandonedcart_active'
                                $(tag).empty();
                                $(tag).append($('<option>', {
                                    value: "0",
                                    text: 'No',
                                    selected: "selected"
                                }));
                                $(tag).append($('<option>', {
                                    value: "1",
                                    text: 'Yes'
                                }));
                                self._hideAbandonedCart();
                            }
                        }
                    }
                );
            },
            _hideAbandonedCart: function () {
                $("#row_mailchimp_abandonedcart_firstdate").hide();
                $("#row_mailchimp_abandonedcart_page").hide();
                $("#row_mailchimp_abandonedcart_save_email_in_quote").hide();
                $("#row_mailchimp_abandonedcart_create_abandonedcart_automation").hide();
            },
            _showEcommerce: function () {
                $("#row_mailchimp_ecommerce_customer_optin").show();
                $("#mailchimp_ecommerce_customer_optin").show();
                $("#row_mailchimp_ecommerce_firstdate").show();
                $("#mailchimp_ecommerce_firstdate").show();
                $("#row_mailchimp_ecommerce_send_promo").show();
                $("#mailchimp_ecommerce_send_promo").show();
                $("#row_mailchimp_ecommerce_including_taxes").show();
                $("#mailchimp_ecommerce_including_taxes").show();
                $("#row_mailchimp_ecommerce_reset_errors_retry").show();
                $("#mailchimp_ecommerce_reset_errors_retry").show();
                $("#row_mailchimp_ecommerce_reset_errors_noretry").show();
                $("#mailchimp_ecommerce_reset_errors_noretry").show();
                $("#row_mailchimp_ecommerce_clean_errors_months").show();
                $("#mailchimp_ecommerce_clean_errors_months").show();
                $("#row_mailchimp_ecommerce_delete_store").show();
                $("#mailchimp_ecommerce_delete_store").show();
                $("#row_mailchimp_ecommerce_resync_products").show();
                $("#mailchimp_ecommerce_resync_products").show();
            },
            _checkCleanButton: function () {
                var checkEcommerceUrl = this.options.checkEcommerceUrl;
                $.ajax({
                    url: checkEcommerceUrl,
                    data: {'form_key': window.FORM_KEY},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == -1) {
                        alert({content: 'Error: can\'t check the unused registers'});
                    } else if (data.valid == 0) {
                        $('#row_mailchimp_general_clean_ecommerce').hide();
                        $('#mailchimp_general_clean_ecommerce').hide();
                    }
                });
            },
            _cleanEcommerce: function () {
                var cleanEcommerceUrl = this.options.cleanEcommerceUrl;
                $.ajax({
                    url: cleanEcommerceUrl,
                    data: {'form_key': window.FORM_KEY},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 0) {
                        alert({content: 'Error: can\'t remove the unused registers'});
                    } else if (data.valid == 1) {
                        alert({content: 'All unused registers are deleted'});
                    }
                });
            },
            _fixMailchimpJS: function ()
            {
                var fixMailchimpjsUrl = this.options.fixMailchimpjsUrl;
                var scope = this.options.scope;
                var scopeId = this.options.scopeId;

                $.ajax({
                    url: fixMailchimpjsUrl,
                    data: {'form_key': window.FORM_KEY,'scope': scope, 'scopeId': scopeId},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 0) {
                        alert({content: 'Error: can\'t fix it'});
                    } else if (data.valid == 1) {
                        alert({content: 'Frontend fixed, please refresh your cache'});
                    }
                });
            },
            _resyncSubscribers: function (listId) {
                var resyncSubscribersUrl = this.options.resyncSubscribersUrl;
                $.ajax({
                    url: resyncSubscribersUrl,
                    data: {'form_key': window.FORM_KEY, 'listId': listId},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 0) {
                        alert({content: 'Error: can\'t resync your subscribers'});
                    } else if (data.valid == 1) {
                        alert({content: 'All subscribers marked for resync'});
                    }
                });
            },
            _resyncProducts: function (mailchimpStoreId) {
                var resyncProductsUrl = this.options.resyncProductsUrl;
                $.ajax({
                    url: resyncProductsUrl,
                    data: {'form_key': window.FORM_KEY, 'mailchimpStoreId': mailchimpStoreId},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 0) {
                        alert({content: 'Error: can\'t resync your products'});
                    } else if (data.valid == 1) {
                        alert({content: 'All products marked for resync'});
                    }
                });
            },
            _createWebhook: function (apiKey, listId) {
                var createWebhookUrl = this.options.createWebhookUrl;
                var scope = this.options.scope;
                var scopeId = this.options.scopeId;
                $.ajax({
                    url: createWebhookUrl,
                    data: {'form_key': window.FORM_KEY, 'apikey': apiKey, 'listId': listId, 'scope': scope, 'scopeId': scopeId},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 0) {
                        alert({content: 'Error: can\'t create WebHook. Your WebHook is already created or your web is private'});
                    } else if (data.valid == 1) {
                        alert({content: 'WebHook created'});
                    }
                });
            },
            _loadStores: function (apiKey) {
                var self = this;
                var storeUrl = this.options.storeUrl;
                // remove all items in list combo
                $('#mailchimp_general_monkeystore').empty();
                // get the selected apikey
                $('#mailchimp_general_monkeystore').append($('<option>', {
                    value: -1,
                    text: 'Select one Mailchimp Store'
                }));
                $('#mailchimp_general_monkeylist').append($('<option>', {
                    value: -1,
                    text: 'Select one Mailchimp Store'
                }));
                // get the list for this apikey via ajax
                $.ajax({
                    url: storeUrl,
                    data: {'form_key': window.FORM_KEY, 'apikey': apiKey, 'encrypt': 0},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    if (data.valid == 1) {
                        var unique = data.stores.length;
                        $.each(data.stores, function (i, item) {
                            if (unique == 1) {
                                $('#mailchimp_general_monkeystore').append($('<option>', {
                                    value: item.id,
                                    text: item.name,
                                    selected: "selected"
                                }));
                            } else {
                                $('#mailchimp_general_monkeystore').append($('<option>', {
                                    value: item.id,
                                    text: item.name
                                }));
                            }
                        });
                        if ($('#mailchimp_general_monkeystore option').length > 1) {
                            $('#row_mailchimp_general_monkeystore').find('.note').hide();
                        } else {
                            $('#row_mailchimp_general_monkeystore').find('.note').show();
                        }
                        self._loadDetails();
                    } else {
                        if (data.errormsg != '') {
                            alert({content: data.errormsg});
                        } else {
                            alert({content: "API Key Invalid"});
                        }
                    }
                });
            },
            _loadDetails: function () {
                var detailsUrl = this.options.detailsUrl;
                var interestUrl = this.options.getInterestUrl;
                var apiKey = $('#mailchimp_general_apikey').val();
                var selectedStore = $('#mailchimp_general_monkeystore').find(':selected').val();
                var encrypt = 0;
                if (apiKey == '******') {
                    encrypt = 3;
                }
                $('#mailchimp_general_account_details_ul').empty();
                $('#mailchimp_general_monkeylist').empty();
                $.ajax({
                    url: detailsUrl,
                    data: {'form_key': window.FORM_KEY, 'apikey': apiKey, "store": selectedStore, 'encrypt': encrypt},
                    type: 'GET',
                    dataType: 'json',
                    showLoader: true
                }).done(function (data) {
                    $.each(data, function (i, item) {
                        if (item.hasOwnProperty('label')) {
                            $('#mailchimp_general_account_details_ul').append('<li>' + item.label + ' ' + item.value + '</li>');
                        }
                    });
                    if (data.list_id) {
                        $('#mailchimp_general_monkeylist').append($('<option>', {
                            value: data.list_id,
                            text: data.list_name,
                            selected: "selected"
                        }));
                    }
                    var selectedList = data.list_id;
                    $('#mailchimp_general_interest').empty();
                    $.ajax({
                        url: interestUrl,
                        data: {'form_key': window.FORM_KEY, 'apikey': apiKey, "list": selectedList, "encrypt": encrypt},
                        type: 'GET',
                        dataType: 'json',
                        showLoader: true
                    }).done(function (data) {
                        if (data.error == 0) {
                            if (data.data.length) {
                                $.each(data.data, function (i, item) {
                                    $('#mailchimp_general_interest').append($('<option>', {
                                        value: item.id,
                                        text: item.title
                                    }));
                                });
                            } else {
                                $('#mailchimp_general_interest').append($('<optgroup>', {
                                    label: '---No Data---'
                                }));
                            }
                        }
                    });
                });
            }
        });
        return $.mage.configmonkeyapikey;
    }
);
