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
        'Magento_Ui/js/modal/confirm',
        'Magento_Ui/js/modal/alert'
    ], function ($ , confirmation, alert) {
        'use strict';

        window.mailchimpdeleteconfirmation = function (ajaxurl) {
            var message = 'Are you sure you want to do this?';
            confirmation( {
                content: message,
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: ajaxurl,
                            data: {form_key: window.FORM_KEY},
                            type: 'POST',
                            success: function (retdata) {
                                if (retdata.valid == 0) {
                                    alert({
                                        content: 'Error: ' + data.message
                                    });
                                }
                                else if (retdata.valid == 1) {
                                    alert({
                                        content: 'Operation OK'
                                    });
                                }
                            }
                        }).done(function (a) {
                            console.log(a);
                        });
                    }
                }
            });
            return false;
        }
    }
);
