import Order from '../PageObjects/objects.cy'

describe('Payments', function () {



    it('CC full capture and refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.clrcookies()
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').wait(3000).then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.clrcookies()
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }

                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })


    it('Klarna full capture and refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('iDEAL Payment', function () {
        const ord = new Order()
        ord.visit()
        cy.get('body').then(($body) => {

            if ($body.text().includes('€') === false) {
                ord.admin()
                ord.change_currency_to_EUR_for_iDEAL()
            }
            //ord.clrcookies()
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.iDEAL_EUR_TERMINAL != "") {
                    cy.get('body').wait(3000).then(($a) => {
                        if ($a.find("label:contains('" + admin.iDEAL_EUR_TERMINAL + "')").length) {
                            ord.ideal_payment(admin.iDEAL_EUR_TERMINAL)
                            ord.admin()
                            ord.ideal_refund()
                        } else {
                            cy.log(admin.iDEAL_EUR_TERMINAL + ' not found in page')
                            this.skip()
                        }

                    })
                }
                else {
                    cy.log('iDEAL_EUR_TERMINAL skipped')
                    this.skip()
                }
            })
        })
    })

    it('CC partial capture', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.partial_capture()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('Klarna partial capture', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.partial_capture()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('CC partial refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.partial_refund()

                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('Klarna partial refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.partial_refund()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('CC release payment', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.release_payment()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('Klarna release payment', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.release_payment()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })

        })
    })

    it('CC Pay by link', function () {
        const ord = new Order()
        ord.clrcookies()

        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {

                    cy.get('#menu-magento-sales-sales').click()
                    cy.get('.item-sales-order > a').click()
                    cy.get('#add').click()
                    cy.reload().wait(3000)
                    cy.contains('Create New Customer').focus().click({force: true}).wait(3000)
                    cy.reload().wait(3000)
                    let text = "";
                    let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
                    for (let i = 0; i < 10; i++) {
                        text += alphabet.charAt(Math.floor(Math.random() * alphabet.length))
                    }
                    cy.get('#email').type(text + '@example.com')
                    cy.get('#order-billing_address_firstname').clear().type('Test')
                    cy.get('#order-billing_address_lastname').clear().type('Person-dk')
                    cy.get('#order-billing_address_street0').clear().type('Nygårdsvej 3A')
                    cy.get('#order-billing_address_city').clear().type('København Ø')
                    cy.get('#order-billing_address_postcode').clear().type('2100')
                    cy.get('#order-billing_address_telephone').clear().type('20123456').wait(3000)
                    cy.get('select[id=order-billing_address_country_id]').select('Denmark')
                    cy.get('.modal-footer > .action-primary > span').click()
                    cy.get('#add_products').click()
                    cy.get('#sales_order_create_search_grid_table > tbody > tr:nth-child(2)').click().wait(5000)
                    cy.contains('Add Selected Product(s) to Order').focus().click({force: true}).wait(5000)
                    cy.contains('Get shipping methods and rates').click({ force: true }).wait(5000)
                    cy.get('.admin__order-shipment-methods-options-list > li:first').click().wait(3000)
                    cy.contains(admin.CC_TERMINAL_NAME).click().wait(3000)
                    cy.get('#submit_order_top_button').click().wait(2000)
                    cy.get('.payment_link > code').then(($a) => {
                        const payment_link = $a.text();
                        cy.origin('https://testgateway.pensio.com', { args: { payment_link } }, ({ payment_link }) => {
                            cy.visit(payment_link)
                            cy.get('#creditCardNumberInput').type('4111111111111111')
                            cy.get('#emonth').type('01')
                            cy.get('#eyear').type('2023')
                            cy.get('#cvcInput').type('123')
                            cy.get('#cardholderNameInput').type('testname')
                            cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(3000)
                        })

                        cy.get('.page-title > span').should('have.text', 'Thank you for your purchase!')
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('Klarna Pay by link', function () {

        const ord = new Order()
        ord.clrcookies()

        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('#menu-magento-sales-sales').click()
                    cy.get('.item-sales-order > a').click()
                    cy.get('#add').click()
                    cy.reload().wait(3000)
                    cy.contains('Create New Customer').focus().click({force: true}).wait(3000)
                    cy.reload().wait(3000)
                    let text = "";
                    let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
                    for (let i = 0; i < 10; i++) {
                        text += alphabet.charAt(Math.floor(Math.random() * alphabet.length))
                    }
                    cy.get('#email').type(text + '@example.com')
                    cy.get('#order-billing_address_firstname').clear().type('Test')
                    cy.get('#order-billing_address_lastname').clear().type('Person-dk')
                    cy.get('#order-billing_address_street0').clear().type('Nygårdsvej 3A')
                    cy.get('#order-billing_address_city').clear().type('København Ø')
                    cy.get('#order-billing_address_postcode').clear().type('2100')
                    cy.get('#order-billing_address_telephone').clear().type('20123456').wait(3000)
                    cy.get('select[id=order-billing_address_country_id]').select('Denmark')
                    cy.get('.modal-footer > .action-primary > span').click()
                    cy.get('#add_products').click()
                    cy.get('#sales_order_create_search_grid_table > tbody > tr:nth-child(2)').click().wait(5000)
                    cy.contains('Add Selected Product(s) to Order').focus().click({force: true}).wait(5000)
                    cy.contains('Get shipping methods and rates').click({ force: true }).wait(5000)
                    cy.get('.admin__order-shipment-methods-options-list > li:first').click().wait(3000)
                    cy.contains(admin.KLARNA_DKK_TERMINAL_NAME).click().wait(3000)
                    cy.get('#submit_order_top_button').click().wait(2000)
                    cy.get('.payment_link > code').then(($a) => {
                        const payment_link = $a.text();
                        cy.origin('https://testgateway.pensio.com', { args: { payment_link } }, ({ payment_link }) => {
                            cy.visit(payment_link).wait(3000)
                            cy.get('#radio_pay_later').click().wait(3000)
                            cy.get('[id=submitbutton]').click().wait(5000)
                            cy.wait(5000)
                            cy.get('[id=klarna-pay-later-fullscreen]').then(function ($iFrame) {
                                const mobileNum = $iFrame.contents().find('[id=email_or_phone]')
                                cy.wrap(mobileNum).type('20222222')
                                const continueBtn = $iFrame.contents().find('[id=onContinue]')
                                cy.wrap(continueBtn).click().wait(2000)
                            })
                            cy.get('[id=klarna-pay-later-fullscreen]').wait(4000).then(function ($iFrame) {
                                const otp = $iFrame.contents().find('[id=otp_field]')
                                cy.wrap(otp).type('123456').wait(2000)
                            })
                            cy.get('[id=klarna-pay-later-fullscreen]').wait(2000).then(function ($iFrame) {
                                const contbtn = $iFrame.contents().find('[id=invoice_kp-purchase-review-continue-button]')
                                cy.wrap(contbtn).click().wait(2000)
                            })

                        })

                    })
                    cy.get('.page-title > span').should('have.text', 'Thank you for your purchase!')
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })

        })
    })

    it('Subscription Payment', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            ord.admin()
            if ($body.text().includes('DKK') === false) {
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.SUBSCRIPTION_TERMINAL_NAME != "") {
                    ord.subscription_product()
                    ord.visit()
                    cy.contains('Argus All-Weather Tank').click()
                    cy.get('#option-label-size-144-item-166').click().wait(2000)
                    cy.get('#option-label-color-93-item-52').click().wait(2000)
                    cy.get('[for="radio_subscribe_product"]').click()
                    cy.get('#product-addtocart-button').click().wait(3000)
                    cy.get('.showcart').click().wait(5000)
                    cy.get('#top-cart-btn-checkout').click().wait(3000)
                    cy.get('#customer-email').type('demo@example.com')
                    cy.get('#pass').type('admin@1234')
                    cy.get('#send2').click().wait(15000)
                    cy.get('.button').click().wait(5000)
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.SUBSCRIPTION_TERMINAL_NAME + "')").length) {
                            cy.contains('Place Order').click().wait(3000)
                            ord.cc_payment('')
                            cy.get('.page-title > span').should('have.text', 'Thank you for your purchase!')
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.SUBSCRIPTION_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                } else {
                    cy.log('SUBSCRIPTION_TERMINAL skipped')
                    this.skip()
                }
            })
        })
    })

    it('MobilePay Pyament ', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            ord.admin()
            if ($body.text().includes('DKK') === false) {
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.MOBILEPAY_TERMINAL_NAME != "") {
                    ord.visit()
                    ord.addproduct()
                    cy.wait(3000)
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.MOBILEPAY_TERMINAL_NAME + "')").length) {
                            cy.contains(admin.MOBILEPAY_TERMINAL_NAME).click().wait(5000)
                            cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action > span').click().wait(15000)
                            cy.get('input[type="tel"]').first().click().clear().click().type(admin.MOBILEPAY_TEST_PHONE)
                            cy.contains('Continue').first().click().wait(10000)
                            cy.url().then(url => {

                                const arr = url.split('&');
                                var paramObj = {};
                                arr.forEach(param => {
                                    const [key, value] = param.split('=');
                                    paramObj[key] = value;
                                });

                                cy.request('POST',
                                    'https://api.sandbox.mobilepay.dk/cardpassthrough-regressiontester-restapi/api/v1/product/payments/simulation/enter-phone-and-swipe/' + paramObj['id'],
                                    { phoneNumber: admin.MOBILEPAY_TEST_PHONE_COUNTRY_CODE + admin.MOBILEPAY_TEST_PHONE }).then(
                                        (response) => {
                                            expect(response.status).to.eq(200)
                                            cy.wait(20000)
                                        })

                            })

                            cy.get('.page-title > span').should('have.text', 'Thank you for your purchase!')
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.MOBILEPAY_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })

                } else {
                    cy.log('MOBILEPAY_TERMINAL skipped')
                    this.skip()
                }
            })
        })
    })
})