/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        var agreementsConfig = window.checkoutConfig.checkoutAgreements;
        return function (messageContainer, method) {

            var serviceUrl,
                payload,
                paymentData = quote.paymentMethod();

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: paymentData,
                    shippingAddress: quote.billingAddress()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: paymentData,
                    billingAddress: quote.billingAddress()
                };
            }

            if (Object.prototype.hasOwnProperty.call(paymentData, '__disableTmpl')) {
                delete paymentData.__disableTmpl;
            }

            if (agreementsConfig.isEnabled) {
                if (jQuery(".payment-method._active .checkout-agreements input[type='checkbox']:checked").length == 0) {
                    paymentData.extension_attributes = {agreement_ids: [""]};
                } else {
                    paymentData.extension_attributes = {agreement_ids: ["1"]};
                }
            }

            fullScreenLoader.startLoader();

            return storage.post(serviceUrl, JSON.stringify(payload)).done(function (data) {
                $('#altapay-error-message').text('');
                var tokenId = '';
                var savecard = false;
                if ($(".payment-method._active select[name='ccToken']").length == 1) {
                    tokenId = $(".payment-method._active select[name='ccToken']").val();
                }
                if ($(".payment-method._active input[name='savecard']").prop("checked") == true) {
                    savecard  = true;
                }
                var paymentMethod = window.checkoutConfig.payment['sdm_altapay'].terminaldata;
                for (var obj in paymentMethod) {
                    if (obj === paymentData.method) {
                        if(paymentMethod[obj].isapplepay !== '1' ) {
                            $.ajax({
                                method: "POST",
                                url: window.checkoutConfig.payment['sdm_altapay'].url,
                                data: {
                                    paytype: method,
                                    cartid: quote.getQuoteId(),
                                    orderid: data,
                                    tokenid: tokenId,
                                    savecard: savecard
                                },
                                dataType: 'json'
                            }).done(function (jsonResponse) {
                                if (jsonResponse.result == 'success') {
                                    window.location.href = jsonResponse.formurl;
                                } else {
                                    fullScreenLoader.stopLoader();
                                    $(".payment-method._active").find('#altapay-error-message').css('display', 'block');
                                    $(".payment-method._active").find('#altapay-error-message').text(jsonResponse.message);
                                    return false;
                                }
                            });
                        }
                    }
                }
            }).fail(function (response) {
                errorProcessor.process(response, messageContainer);
                fullScreenLoader.stopLoader();
            });
        };
    }
);
