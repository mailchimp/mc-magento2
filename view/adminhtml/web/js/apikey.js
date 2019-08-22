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
    function ($,alert) {
        "use strict";

        $.widget('mage.monkeyapikey', {
            "options": {
                "apikeyUrl": ""
            },

            _init: function () {
                var apiUrl = this.options.apikeyUrl;
                $('#stores_apikey').change(function () {
                    // remove all items in list combo
                    $('#stores_list_id').empty();
                    // get the selected apikey
                    var apiKey = $('#stores_apikey').find(':selected').val();
                    // get the list for this apikey via ajax
                    //var apiUrl = this.options.apikeyUrl;
                    $.ajax({
                        url: apiUrl,
                        data: {'form_key':  window.FORM_KEY, 'apikey': apiKey, 'encrypt': 1},
                        type: 'POST',
                        dataType: 'json',
                        showLoader: true
                    }).done(function (data) {
                        if (data.valid==1) {
                            $.each(data.lists, function (i, item) {
                                $('#stores_list_id').append($('<option>', {
                                    value: item.id,
                                    text: item.name
                                }));
                            });
                        } else {
                            alert({content:data.errormsg});
                        }
                    });
                });
            }
        });
        return $.mage.monkeyapikey;
    }
);