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
        'Magento_Ui/js/modal/alert'
    ],
    function ($, alert) {
        "use strict";

        $.widget('mage.configmonkeyapikey', {
            "options": {
                "storeUrl": "",
                "detailsUrl": "",
                "storeGridUrl": "",
                "createWebhookUrl": "",
                "getInterestUrl": "",
                "resyncSubscribersUrl": "",
                "resyncProductsUrl": ""
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
                    var mailchimpStoreId = $('#mailchimp_general_monkeystore').find(':selected').val();
                    self._resyncSubscribers(mailchimpStoreId);
                });
                $('#mailchimp_ecommerce_resync_products').click(function () {
                    var mailchimpStoreId = $('#mailchimp_general_monkeystore').find(':selected').val();
                    self._resyncProducts(mailchimpStoreId);
                });

            },
            _resyncSubscribers: function (mailchimpStoreId) {
                var resyncSubscribersUrl = this.options.resyncSubscribersUrl;
                $.ajax({
                    url: resyncSubscribersUrl,
                    data: {'form_key': window.FORM_KEY, 'mailchimpStoreId': mailchimpStoreId},
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
                $.ajax({
                    url: createWebhookUrl,
                    data: {'form_key': window.FORM_KEY, 'apikey': apiKey, 'listId': listId},
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