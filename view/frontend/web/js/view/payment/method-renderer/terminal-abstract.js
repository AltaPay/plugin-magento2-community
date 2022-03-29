/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'SDM_Altapay/js/action/set-payment',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function ($, Component, storage, Action, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Altapay/payment/terminal',
                terminal: '1'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function () {
                var self = this;
                var paymentMethod = window.checkoutConfig.payment['sdm_altapay'].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if(paymentMethod[obj].isapplepay === '1' ) {
                            this.onApplePayButtonClicked();
                        }
                    }
                }
                $('#altapay-error-message').text('');
                var auth = window.checkoutConfig.payment[this.getDefaultCode()].auth;
                var connection = window.checkoutConfig.payment[this.getDefaultCode()].connection;
                if (!auth || !connection) {
                    $(".payment-method._active").find('#altapay-error-message').css('display', 'block');
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
            terminalName: function () {
                var self = this;
                var terminalname;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].terminallogo != "" && paymentMethod[obj].showlogoandtitle == false) {
                            terminalname = "";
                        } else {
                            if (paymentMethod[obj].terminalname != " ") {
                                    if (paymentMethod[obj].label != null) {
                                        terminalname = paymentMethod[obj].label
                                    } else {
                                        terminalname = paymentMethod[obj].terminalname;
                                    }
                            }
                        }
                    }
                }
                return terminalname;
            },
            terminalStatus: function () {
                var self = this;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].terminalname == " ") {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }

            },
            onApplePayButtonClicked: function() {
                configData = window.checkoutConfig;
                var baseurl = configData.url;

                if (!ApplePaySession) {
                    return;
                }

                // Define ApplePayPaymentRequest
                const request = {
                    "countryCode": configData.countryCode,
                    "currencyCode": configData.currencyCode,
                    "merchantCapabilities": [
                        "supports3DS"
                    ],
                    "supportedNetworks": [
                        "visa",
                        "masterCard",
                        "amex",
                        "discover"
                    ],
                    "total": {
                        "label": "Demo (Card is not charged)",
                        "type": "final",
                        "amount": configData.grandTotalAmount
                    }
                };
                
                // Create ApplePaySession
                const session = new ApplePaySession(3, request);

                session.onvalidatemerchant = async event => {
                    var url = baseurl+"sdmaltapay/index/applepay";
                    // Call your own server to request a new merchant session.            
                    $.ajax({
                        url: url,
                        data: {
                            validationUrl: event.validationURL,
                            termminalid: this.terminalName()
                        },
                        type: 'post',
                        dataType: 'JSON',
                        success: function(response) {
                                var responsedata = jQuery.parseJSON(response);
                                session.completeMerchantValidation(responsedata);
                        }
                    });
                };
                
                session.onpaymentmethodselected = event => {
                    let total = {
                        "label": "AltaPay Apple Charge",
                        "type": "final",
                        "amount": configData.grandTotalAmount
                    }
            
                    const update = { "newTotal": total };
                    session.completePaymentMethodSelection(update);
            
                };
                
                session.onshippingmethodselected = event => {
                    // Define ApplePayShippingMethodUpdate based on the selected shipping method.
                    // No updates or errors are needed, pass an empty object. 
                    const update = {};
                    session.completeShippingMethodSelection(update);
                };
                
                session.onshippingcontactselected = event => {
                    // Define ApplePayShippingContactUpdate based on the selected shipping contact.
                    const update = {};
                    session.completeShippingContactSelection(update);
                };
                
                session.onpaymentauthorized = event => {
                    var url = baseurl+"sdmaltapay/index/applepayresponse";    
                    $.ajax({
                        url: url,
                        data: {
                            providerData: JSON.stringify(event.payment.token),
                            paytype: 1
                        },
                        type: 'post',
                        dataType: 'JSON',
                        complete: function(response) {
                            var status;
                            var responsestatus = response.responseJSON.Result.toLowerCase();
                            if (responsestatus === 'success') {
                                status = ApplePaySession.STATUS_SUCCESS;
                                session.completePayment(status);
                                redirectOnSuccessAction.execute();
                            } else {
                                status = ApplePaySession.STATUS_FAILURE;
                                session.completePayment(status);
                            }
                        }
                    });        
                };
                session.oncancel = event => {
                    console.log("oncancel");
                };
                
                session.begin();
            },
            getDefaultCode: function () {
                return 'sdm_altapay';
            },
            terminalLogo: function () {
                var self = this;
                var terminallogo;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;

                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].terminallogo != " ") {
                            if (paymentMethod[obj].terminallogo != null) {
                                terminallogo = paymentMethod[obj].terminallogo
                            }
                        }
                    }
                }
                return terminallogo;
            },
            savedTokenList: function () {
                var self = this;
                var savedtokenlist;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].savedtokenlist != " ") {
                            if (paymentMethod[obj].savedtokenlist != null) {
                                savedtokenlist = JSON.parse(paymentMethod[obj].savedtokenlist)
                            }
                        }
                    }
                }
                return savedtokenlist;
            },
            savedTokenPrimaryOption: function () {
                var self = this;
                var savedtokenprimaryoption;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].savedtokenprimaryoption != " ") {
                            if (paymentMethod[obj].savedtokenprimaryoption != null) {
                                savedtokenprimaryoption = paymentMethod[obj].savedtokenprimaryoption
                            }
                        }
                    }
                }
                return savedtokenprimaryoption;
            }
        });
    }
);
