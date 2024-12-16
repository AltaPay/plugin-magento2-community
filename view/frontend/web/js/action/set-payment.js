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
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'underscore',
        'Magento_Customer/js/customer-data'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, redirectOnSuccessAction, _, customerData) {
        'use strict';
        var agreementIds = [];
        var checkoutConfig = window.checkoutConfig;

        /**
         * Filter template data.
         *
         * @param {Object|Array} data
         */
        var filterTemplateData = function (data) {
            return _.each(data, function (value, key, list) {
                if (_.isArray(value) || _.isObject(value)) {
                    list[key] = filterTemplateData(value);
                }
                if (key === '__disableTmpl' || key === 'title') {
                    delete list[key];
                }
            });
        };

        return function (messageContainer, method, applePay) {

            var serviceUrl,
                payload,
                paymentData = quote.paymentMethod();

            payload = {
                cartId: quote.getQuoteId(),
                paymentMethod: filterTemplateData(paymentData)
            };

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload.email = quote.guestEmail;
                payload.shippingAddress = quote.billingAddress();
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
                payload.billingAddress = quote.billingAddress();
            }

            var agreementsConfig = checkoutConfig.checkoutAgreements;
            if (agreementsConfig.isEnabled) {
                var checkoutAgreements = (agreementsConfig && agreementsConfig.agreements) ? agreementsConfig.agreements : [];
                for (let i = 0; i < checkoutAgreements.length; i++) {
                    agreementIds[i] = checkoutAgreements[i].agreementId;
                }
                paymentData.extension_attributes = {agreement_ids: agreementIds};
            }

            fullScreenLoader.startLoader();

            return storage.post(serviceUrl, JSON.stringify(payload)).done(function (data) {
                if (applePay) {
                    $.ajax({
                        url: applePay.url,
                        data: {
                            providerData: applePay.providerData,
                            paytype: applePay.method,
                            orderid: data
                        },
                        type: 'post',
                        dataType: 'JSON',
                        success: function (response) {
                            if (response && response.status === "success") {
                                customerData.invalidate(['checkout-data']);
                                applePay.session.completePayment(ApplePaySession.STATUS_SUCCESS);
                                redirectOnSuccessAction.execute();
                            } else {
                                applePay.session.completePayment(ApplePaySession.STATUS_FAILURE);
                                fullScreenLoader.stopLoader();
                                $(".payment-method._active").find('#altapay-error-message').text(applePay.mag_trans('error occured')).show().delay(5000).fadeOut();
                            }
                        },
                        error: function () {
                            applePay.session.completePayment(ApplePaySession.STATUS_FAILURE);
                            fullScreenLoader.stopLoader();
                            $(".payment-method._active").find('#altapay-error-message').text(applePay.mag_trans('error occured')).show().delay(5000).fadeOut();
                        }
                    });
                } else {
                    var tokenId = '';
                    var savecard = 0;
                    if ($(".payment-method._active select[name='ccToken']").length == 1) {
                        tokenId = $(".payment-method._active select[name='ccToken']").val();
                    }
                    if ($(".payment-method._active input[name='savecard']").prop("checked") == true) {
                        savecard = 1;
                    }

                    $.ajax({
                        method: "POST",
                        url: checkoutConfig.payment['sdm_altapay'].url,
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
                            customerData.invalidate(['checkout-data']);
                            window.location.href = jsonResponse.formurl;
                        } else {
                            fullScreenLoader.stopLoader();
                            $(".payment-method._active").find('#altapay-error-message').css('display', 'block');
                            $(".payment-method._active").find('#altapay-error-message').text(jsonResponse.message);
                            return false;
                        }
                    });
                }
            }).fail(function (response) {
                if (applePay) {
                    applePay.session.completePayment(ApplePaySession.STATUS_FAILURE);
                }
                errorProcessor.process(response, messageContainer);
                fullScreenLoader.stopLoader();
            });
        };
    }
);
