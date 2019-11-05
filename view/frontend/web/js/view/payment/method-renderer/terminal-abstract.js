/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'SDM_Valitor/js/action/set-payment'
    ],
    function ($, Component, storage, Action) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Valitor/payment/terminal',
                terminal: '1'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function () {
                $('#valitor-error-message').text('');
                var auth = window.checkoutConfig.payment[this.getDefaultCode()].auth;
                var connection = window.checkoutConfig.payment[this.getDefaultCode()].connection;
                if (!auth || !connection) {
                    $(".payment-method._active").find('#valitor-error-message').css('display','block');
                    $(".payment-method._active").find('#valitor-error-message').text('Could not authenticate with API');
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
            terminalName: function () {
                var self = this;
                var terminalname;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for(var obj in paymentMethod) {
                    if(obj === self.getCode()) {
                        if(paymentMethod[obj].terminalname != " "){
                            if(paymentMethod[obj].label != null){
                                terminalname = paymentMethod[obj].label
                            }else {
                                terminalname = paymentMethod[obj].terminalname;
                            }
                        }                        
                    }
                }
                return terminalname;
            },
            terminalStatus: function () {
                var self = this;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for(var obj in paymentMethod) {
                    if(obj === self.getCode()) {
                        if(paymentMethod[obj].terminalname == " "){
                            return false;
                        } else {
                            return true;
                        }                   
                    }
                }

            },
            getDefaultCode: function () {
                return 'sdm_valitor';
            }
        });
    }
);
