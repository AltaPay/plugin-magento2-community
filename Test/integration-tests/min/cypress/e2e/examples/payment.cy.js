import Order from '../PageObjects/objects.cy'

describe('Payments', function () {

    it('Terminals settings', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        cy.get('#menu-magento-backend-stores > [onclick="return false;"]').click({ force: true })
        cy.get('.item-system-config > a').click()
        cy.get(':nth-child(5) > .admin__page-nav-title').click().wait(3000)
        cy.get(':nth-child(10) > .admin__page-nav-link').click({ force: true })

        const terminals = [
            "CC_TERMINAL_NAME", 
            "iDEAL_EUR_TERMINAL", 
            "KLARNA_DKK_TERMINAL_NAME", 
            "MOBILEPAY_TERMINAL_NAME", 
            "SUBSCRIPTION_TERMINAL_NAME",
            "BANCONTACT_TERMINAL_NAME",
        ];

        //Setting terminals to their payment method
        cy.fixture('config').then((admin) => {
            terminals.forEach(function(terminal, index){
                if (admin[terminal]) {
                    cy.get('#payment_us_sdm_altapay_config_terminal'+(index + 1)+'-head').click().wait(3000)
                    cy.get('#payment_us_sdm_altapay_config_terminal'+(index + 1)+'_active').select('Yes')
                    if(terminal == "SUBSCRIPTION_TERMINAL_NAME"){
                        cy.get('#payment_us_sdm_altapay_config_terminal'+(index + 1)+'_terminalname').select(admin.CC_TERMINAL_NAME)
                    }else{
                        cy.get('#payment_us_sdm_altapay_config_terminal'+(index + 1)+'_terminalname').select(admin[terminal]).wait(3000)
                    }
                    cy.get('#payment_us_sdm_altapay_config_terminal'+(index + 1)+'_title').clear().type(admin[terminal])
                }
            });
        })

        cy.get('#save').click().wait(3000)


    })
    
    it('iDEAL Payment', function () {
        const ord = new Order()
        ord.visit()
        cy.get('body').then(($body) => {

            if ($body.text().includes('€') === false) {
                ord.admin()
                ord.change_currency_to_EUR_for_iDEAL()
            }
            ord.clrcookies()
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

    it.skip('CC Pay by link', function () {
        const ord = new Order()
        ord.clrcookies()

        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {

                    ord.create_customer_and_order();
                    cy.contains(admin.CC_TERMINAL_NAME).click().wait(3000)
                    cy.get('#submit_order_top_button').click().wait(2000)
                    cy.get('.payment_link > code').then(($a) => {
                        const payment_link = $a.text();
                        cy.origin('https://testgateway.pensio.com', { args: { payment_link } }, ({ payment_link }) => {
                            cy.visit(payment_link)
                            cy.get('#creditCardNumberInput').type('4111111111111111')
                            cy.get('#emonth').select('12')
                            cy.get('#eyear').select('2025')
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
    
    it.skip('Klarna Pay by link', function () {

        const ord = new Order()
        ord.clrcookies()

        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    ord.create_customer_and_order();
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
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
                        }else{
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME+' skipped')
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

    it.skip('Klarna partial capture', function () {
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

    it.skip('Klarna partial refund', function () {
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

    it.skip('Klarna release payment', function () {

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

    it.skip('Subscription payment', function () {
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
                    cy.contains('Create an Account').click().wait(3000)
                    cy.get('#firstname').type('Test')
                    cy.get('#lastname').type('Test Person')
                    var text = ord.generateRandomString(8);
                    cy.get('#email_address').type(text + '@example.com')
                    cy.get('#password').type(text + '@')
                    cy.get('#password-confirmation').type(text + '@')
                    cy.get('#form-validate > .actions-toolbar > div.primary > #send2').click()
                    cy.get('img').click()
                    cy.contains('Argus All-Weather Tank').click()
                    cy.get('#option-label-size-144-item-166').click().wait(2000)
                    cy.get('#option-label-color-93-item-52').click().wait(2000)
                    cy.get('[for="radio_subscribe_product"]').click()
                    cy.get('#product-addtocart-button').click().wait(3000)
                    cy.get('.showcart').click().wait(5000)
                    cy.get('#top-cart-btn-checkout').click().wait(3000)
                    cy.get('input[name=firstname]').type('Testperson-dk')
                    cy.get('input[name=lastname]').type('Approved')
                    cy.get('select[name=country_id]').select('Denmark')
                    cy.get('body').then(($p) => {
                        if ($p.find('select[name=region_id]').length) {
                            cy.get('select[name=region_id]').select('Hovedstaden')
                        }
                    })
                    cy.get('input[name="street[0]"]').type('Sæffleberggate 56,1 mf')
                    cy.get('input[name=city]').type('Varde')
                    cy.get('input[name=postcode]').type('6800')
                    cy.get('input[name=telephone]').type('20123456')
                    cy.wait(5000)
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

    it.skip('MobilePay payment ', function () {
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