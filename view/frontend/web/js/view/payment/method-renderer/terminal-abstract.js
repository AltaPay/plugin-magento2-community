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
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function ($, Component, storage, Action, redirectOnSuccessAction, quote, totals, additionalValidators, $t, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Altapay/payment/terminal',
                terminal: '1'
            },
            configData: null,
            redirectAfterPlaceOrder: false,
            initialized: false,
            initialize() {
                this._super();
                this.configData = window.checkoutConfig.payment[this.getDefaultCode()];
                if (this.configData.terminaldata[this.getCode()].isapplepay === '1' && !this.initialized) {
                    let self = this;
                    this.initialized = true;
                    $('body').on('fc:placeOrderBefore', function() {
                        let paymentMethod = window.checkoutConfig.payment['sdm_altapay'].terminaldata,
                            selectedTerminal = $('input[name="payment[method]"]:checked').attr('id');
                        for (let method in paymentMethod) {
                            if (method === selectedTerminal && paymentMethod[method].isapplepay === '1') {
                                self.placeOrder();
                                break;
                            }
                        }
                    });
                }
                return this;
            },
            placeOrder: function() {
                if (this.configData.terminaldata[this.getCode()].isapplepay === '1') {
                    if(!additionalValidators.validate()){
                        return;
                    }
                    this.onApplePayButtonClicked();
                }

                var auth = window.checkoutConfig.payment[this.getDefaultCode()].auth;
                var connection = window.checkoutConfig.payment[this.getDefaultCode()].connection;
                if (!auth || !connection) {
                    this.messageContainer.addErrorMessage({
                        message: $t('Could not authenticate with API')
                    });
                    return false;
                }

                var self = this;
                if (self.validate() && additionalValidators.validate()) {
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
                var isSafari = (/^((?!chrome|android).)*safari/i.test(navigator.userAgent));
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if ((paymentMethod[obj].terminallogo != "" && paymentMethod[obj].showlogoandtitle == false) ||
                            (paymentMethod[obj].isapplepay == 1 && isSafari === false)) {
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
            terminalMessage: function () {
                var self = this;
                var terminalmessage;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;

                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].terminalmessage != "" && paymentMethod[obj].terminalmessage != null) {
                            terminalmessage = paymentMethod[obj].terminalmessage
                        }
                    }
                }
                return terminalmessage;
            },
            terminalStatus: function () {
                var self = this;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                var isSafari = (/^((?!chrome|android).)*safari/i.test(navigator.userAgent));
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].terminalname == " " || paymentMethod[obj].isapplepay == 1 && isSafari === false) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }

            },
            onApplePayButtonClicked: function() {
                if (!ApplePaySession) {
                    return;
                }
                var total = totals.getSegment('grand_total').value;
                var grandTotal = this.configData.currencyConfig ? quote.totals().base_grand_total : total;
                var applePayLabel = 'Apple Pay';

                if(this.configData.terminaldata[this.getCode()].applepaylabel !== null){
                    applePayLabel = this.configData.terminaldata[this.getCode()].applepaylabel;
                }

                // Define ApplePayPaymentRequest
                const request = {
                    "countryCode": this.configData.countryCode,
                    "currencyCode": this.configData.currencyCode,
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
                        "label": applePayLabel,
                        "type": "final",
                        "amount": grandTotal
                    }
                };

                // Create ApplePaySession
                const session = new ApplePaySession(3, request);

                session.onvalidatemerchant = async event => {
                    var url = this.configData.baseUrl + "sdmaltapay/index/applepay";
                    // Call your own server to request a new merchant session.
                    $.ajax({
                        url: url,
                        data: {
                            validationUrl: event.validationURL,
                            termminalid: this.configData.terminaldata[this.getCode()].terminalname
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
                        "label": applePayLabel,
                        "type": "final",
                        "amount": grandTotal
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
                    var method = this.terminal.substr(this.terminal.indexOf(" ") + 1);
                    var url = this.configData.baseUrl + "sdmaltapay/index/applepayresponse";

                    $.ajax({
                        url: url,
                        data: {
                            providerData: JSON.stringify(event.payment.token),
                            paytype: method
                        },
                        type: 'post',
                        dataType: 'JSON',
                        complete: function(response) {
                            var status;
                            if (response.status === 200 && response.statusText === "OK" && response.responseJSON.status === 'success') {
                                status = ApplePaySession.STATUS_SUCCESS;
                                session.completePayment(status);
                                redirectOnSuccessAction.execute();
                            } else {
                                status = ApplePaySession.STATUS_FAILURE;
                                session.completePayment(status);
                                fullScreenLoader.stopLoader();
                                $(".payment-method._active").find('#altapay-error-message').text($t('error occured')).show().delay(5000).fadeOut();
                            }
                        }
                    });
                };
                session.oncancel = event => {
                    var url = this.configData.baseUrl + "sdmaltapay/index/cancel";
                    $.ajax({
                        url: url,
                        type: 'post',
                        success: function(data, status, xhr) {
                            fullScreenLoader.stopLoader();
                        }
                    });
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

                        if (paymentMethod[obj].terminallogo != " " && paymentMethod[obj].terminallogo != null) {
                            terminallogo = paymentMethod[obj].terminallogo
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
                        if (paymentMethod[obj].savedtokenlist != " " && paymentMethod[obj].savedtokenlist != null) {
                            if((Object.keys(JSON.parse(paymentMethod[obj].savedtokenlist)).length) > 1){
                                savedtokenlist = JSON.parse(paymentMethod[obj].savedtokenlist);
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
            },
            enableSaveCard: function () {
                var self = this;
                var enableSaveCard = 0;
                var paymentMethod = window.checkoutConfig.payment[this.getDefaultCode()].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === self.getCode()) {
                        if (paymentMethod[obj].enabledsavetokens != null && paymentMethod[obj].isLoggedIn != null) {
                            enableSaveCard = paymentMethod[obj].enabledsavetokens;
                        }
                    }
                }

                return enableSaveCard;
            }
        });
    }
);
