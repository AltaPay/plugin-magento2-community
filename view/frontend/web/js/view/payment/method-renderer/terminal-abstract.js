/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'SDM_Altapay/js/action/set-payment'
    ],
    function ($, Component, storage, Action) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Altapay/payment/terminal',
                terminal: '1'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function () {
                $('#altapay-error-message').text('');
                var auth = window.checkoutConfig.payment[this.getDefaultCode()].auth;
                var connection = window.checkoutConfig.payment[this.getDefaultCode()].connection;
                if (!auth || !connection) {
                    $(".payment-method._active").find('#altapay-error-message').css('display','block');
                    $(".payment-method._active").find('#altapay-error-message').text('Could not authenticate with API');
                    return false;
                }

                var self = this;
                if (self.validate()) {
                    self.selectPaymentMethod();
                    Action(
                        this.messageContainer,
                        this.terminal
                    );
                }
            },

            getDefaultCode: function () {
                return 'sdm_altapay';
            }
        });
    }
);
