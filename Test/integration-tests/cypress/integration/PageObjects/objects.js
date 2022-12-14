require('cypress-xpath')

class Order {
    clrcookies() {
        cy.clearCookies()
    }
    visit() {
        cy.fixture('config').then((url) => {
            cy.visit(url.shopURL)
        })
    }


    addproduct() {
        cy.contains('Fusion Backpack').wait(3000).click()
        cy.wait(3000)
        cy.get('#product-addtocart-button').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click().wait(2000)
        cy.get('.checkout-methods-items > :nth-child(1) > .action').wait(5000).click().wait(10000)
        cy.get('#customer-email-fieldset input[name=username]#customer-email').type('demo@example.com')
        cy.get('input[name=firstname]').type('Testperson-dk')
        cy.get('input[name=lastname]').type('Approved')
        cy.get('select[name=country_id]').select('Denmark')
        cy.get('input[name="street[0]"]').type('Sæffleberggate 56,1 mf')
        cy.get('input[name=city]').type('Varde')
        cy.get('input[name=postcode]').type('6800')
        cy.get('input[name=telephone]').type('20123456')
        cy.wait(5000)
        cy.get('body').then(($p) => {
            if ($p.find('.radio').length) {
                cy.get('.radio').click({ multiple: true }).wait(2000)
            }
        })
        cy.get('.button').click().wait(5000)
    }

    cc_payment(CC_TERMINAL_NAME) {
        cy.contains(CC_TERMINAL_NAME).click({ force: true })
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(2000)
        cy.get('#creditCardNumberInput').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(3000)
    }

    klarna_payment(KLARNA_DKK_TERMINAL_NAME) {
        cy.contains(KLARNA_DKK_TERMINAL_NAME).click({ force: true })
        cy.wait(3000)
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(5000)
        cy.get('[id=submitbutton]').click().wait(5000)
        cy.wait(5000)
        cy.get('[id=klarna-pay-later-fullscreen]').then(function ($iFrame) {
            const mobileNum = $iFrame.contents().find('[id=email_or_phone]')
            cy.wrap(mobileNum).type('20222222')
            const continueBtn = $iFrame.contents().find('[id=onContinue]')
            cy.wrap(continueBtn).click().wait(2000)
        })
        cy.get('[id=klarna-pay-later-fullscreen]').wait(4000).then(function($iFrame){
            const otp = $iFrame.contents().find('[id=otp_field]')
            cy.wrap(otp).type('123456').wait(2000)
        })  
        cy.get('[id=klarna-pay-later-fullscreen]').wait(2000).then(function($iFrame){
            const contbtn = $iFrame.contents().find('[id=invoice_kp-purchase-review-continue-button]')
            cy.wrap(contbtn).click().wait(2000)
        })
    }

    admin() {
        cy.fixture('config').then((conf) => {
            cy.clearCookies()
            cy.visit(conf.adminURL).wait(8000)
            cy.get('#username').type(conf.adminUsername)
            cy.get('#login').type(conf.adminPass)
            cy.get('.action-login').click().wait(5000)
            cy.get('body').then(($p) => {
                if ($p.find(".action-secondary").length) {
                    cy.get('.action-secondary').click()
                }
            })
        })

    }

    capture() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_invoice > span').wait(5000).click()
        cy.wait(6000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
        cy.wait(6000)

    }

    refund() {
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('#credit-memo > span').click()
        cy.wait(2000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]/span').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    subscription_product() {
        cy.get('img').click()
        cy.contains('Push It Messenger Bag').click({ force: true }).wait(5000)

    }
    subscrition_check() {
        cy.get('[for="radio_subscribe_product"]').wait(1000).click()
        cy.get('#product-addtocart-button').click()
        cy.wait(2000)
        cy.get('.message-success > div > a').click()
        cy.wait(5000)
        cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
        cy.wait(3000)

        cy.get('.button').click().wait(3000)

    }


    //Subscription payment
    subscription_payment() {
        cy.fixture('config').then((admin) => {
            cy.contains(admin.SUBSCRIPTION_TERMINAL_NAME).click({ force: true })
            cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(3000)
            cy.get('#creditCardNumberInput').type('4111111111111111')
            cy.get('#emonth').type('01')
            cy.get('#eyear').type('2023')
            cy.get('#cvcInput').type('123')
            cy.get('#cardholderNameInput').type('testname')
            cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(6000)

        })
    }


    signin() {
        cy.contains('Create an Account').click()
        cy.get('#firstname').type('Testperson-dk')
        cy.get('#lastname').type('Testperson-dk')

        function generateNewUsername() {
            let text = "";
            let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"

            for (let i = 0; i < 10; i++)
                text += alphabet.charAt(Math.floor(Math.random() * alphabet.length))
            return text;

        }
        const generatedUsername = generateNewUsername()
        cy.get('#email_address').type(generatedUsername + '@example.com')
        cy.get('#password').type('P@ssword123')
        cy.get('#password-confirmation').type('P@ssword123')
        cy.get('#form-validate > .actions-toolbar > div.primary > .action').click()

        //Manage Shipping details
        cy.contains('Manage Addresses').click()
        cy.get('#street_1').type('Sæffleberggate 56,1 mf')
        cy.get('#telephone').type('20123456')
        cy.get('#country').select('Denmark')
        cy.get('#city').type('Varde')
        cy.get('#zip').type('6800')
        cy.get('#form-validate > .actions-toolbar > div.primary > .action').click()
    }

    partial_capture() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_invoice > span').wait(5000).click()
        cy.wait(6000)
        cy.get('.even > :nth-child(1) > .col-qty-invoice > .input-text').clear().type('0')
        cy.contains("Update Qty's").click()
        cy.wait(6000)
        cy.contains('Submit Invoice').click()
        cy.wait(6000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')
    }

    addpartial_product() {
        cy.contains('Push It Messenger Bag').wait(3000).click()
        cy.get('#product-addtocart-button').click()
        cy.wait(3000)
    }

    partial_refund() {
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('#credit-memo > span').click()
        cy.wait(2000)
        cy.get('.even > :nth-child(1) > .col-refund > .input-text').clear().type('0')
        cy.get('.col-refund > span').click()
        cy.contains("Update Qty's").click().wait(2000)
        cy.xpath('/html/body/div[3]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    release_payment() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order-view-cancel-button').click()
        cy.get('.confirm > .modal-inner-wrap > .modal-footer > .action-primary').click()

    }

    create_cart_percent_discount() {
        //Check if catalog discount is active. If so, deactivate it.
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"]').click()
        cy.get('.item-promo-catalog > a').wait(5000).click()
        cy.contains("AltaPay Catalog Rule Percentage").click().wait(5000)
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"]').wait(5000).click()
        cy.get('.item-promo-catalog > a').wait(3000).click()
        cy.contains("AltaPay Catalog Rule Fixed").click().wait(3000)
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })
        //Start creating/activating cart discount    
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-quote > a > span').click()
        cy.contains('$4 Luma water bottle (save 70%)').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                }
            })
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[2]/div[2]/fieldset/div[7]/div[2]/input").clear().type("percentage")
        cy.get('[data-index="actions"] > .fieldset-wrapper-title > .admin__collapsible-title').click()
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[1]/div[2]/select").select("Percent of product price discount")
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[2]/div[2]/input").clear().type("10")
        cy.get('body').then(($p) => {
            if ($p.find(".rule-param-remove > .v-middle").length) {
                cy.get('.rule-param-remove > .v-middle').click()
            }
        })

        cy.get('#save').click()
    }


    apply_cart_percent_discount() {
        cy.contains('Fusion Backpack').wait(3000).click()
        cy.get('#product-addtocart-button').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click().wait(2000)
        cy.get('#block-discount > .title').click()
        cy.get('#coupon_code').type("percentage")
        cy.get('#discount-coupon-form > .fieldset > .actions-toolbar > div.primary > .action > span').click()
    }

    complete_checkout() {

        cy.get('.checkout-methods-items > :nth-child(1) > .action').wait(5000).click().wait(5000)
        cy.get('#customer-email-fieldset input[name=username]#customer-email').type('demo@example.com')
        cy.get('input[name=firstname]').type('Testperson-dk')
        cy.get('input[name=lastname]').type('Approved')
        cy.get('select[name=country_id]').select('Denmark')
        cy.get('input[name="street[0]"]').type('Sæffleberggate 56,1 mf')
        cy.get('input[name=city]').type('Varde')
        cy.get('input[name=postcode]').type('6800')
        cy.get('input[name=telephone]').type('20123456')
        cy.get('.radio').click({ multiple: true })
        cy.wait(1000)
        cy.get('.button').click().wait(5000)
    }

    create_cart_fixed_discount() {
        //Check if catalog discount is active. If so, deactivate it.
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"]').click()
        cy.get('.item-promo-catalog > a').click()
        cy.contains("AltaPay Catalog Rule Percentage").click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"]').click()
        cy.get('.item-promo-catalog > a').click()
        cy.contains("AltaPay Catalog Rule Fixed").click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })

        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-quote > a > span').click()
        cy.contains('$4 Luma water bottle (save 70%)').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                }
            })
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[2]/div[2]/fieldset/div[7]/div[2]/input").clear().type("fixed")
        cy.get('[data-index="actions"] > .fieldset-wrapper-title > .admin__collapsible-title').click()
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[1]/div[2]/select").select("Fixed amount discount")
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[2]/div[2]/input").clear().type("10")
        cy.get('body').then(($p) => {
            if ($p.find(".rule-param-remove > .v-middle").length) {
                cy.get('.rule-param-remove > .v-middle').click()
            }
        })
        cy.get('#save').click()
    }

    apply_cart_fixed_discount() {
        cy.contains('Fusion Backpack').wait(3000).click().wait(2000)
        cy.get('#product-addtocart-button').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click().wait(2000)
        cy.get('#block-discount > .title').click()
        cy.get('#coupon_code').type("fixed")
        cy.get('#discount-coupon-form > .fieldset > .actions-toolbar > div.primary > .action > span').click()
    }

    create_catalog_percentage_discount() {
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-catalog > a > span').click()
        cy.contains('AltaPay Catalog Rule Fixed').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.wait(50000)
                    cy.get('#save_and_apply').click()
                }
            })
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-catalog > a > span').click()
        cy.contains('AltaPay Catalog Rule Percentage').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })
    }

    create_catalog_fixed_discount() {
        //Check if catalog discount is active. If so, deactivate it.
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"]').click()
        cy.get('.item-promo-catalog > a').click()
        cy.contains("AltaPay Catalog Rule Percentage").click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "1") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.get('#save_and_apply').click()
                    cy.wait(50000)
                }
            })
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-catalog > a > span').click()
        cy.contains('AltaPay Catalog Rule Fixed').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                    cy.wait(50000)
                    cy.get('#save_and_apply').click()
                }
            })
    }

    create_cart_percentage_with_catalog() {
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-quote > a > span').click()
        cy.contains('$4 Luma water bottle (save 70%)').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                }
            })
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[2]/div[2]/fieldset/div[7]/div[2]/input").clear().type("percentage")
        cy.get('[data-index="actions"] > .fieldset-wrapper-title > .admin__collapsible-title').click()
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[1]/div[2]/select").select("Percent of product price discount")
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[2]/div[2]/input").clear().type("10")
        cy.get('body').then(($p) => {
            if ($p.find(".rule-param-remove > .v-middle").length) {
                cy.get('.rule-param-remove > .v-middle').click()
            }
        })
        cy.get('#save').click()
    }

    create_cart_fixed_with_catalog() {
        cy.get('#menu-magento-backend-marketing > [onclick="return false;"] > span').click()
        cy.get('.item-promo-quote > a > span').click()
        cy.contains('$4 Luma water bottle (save 70%)').wait(1000).click()
        cy.get('input[name="is_active"]')
            .invoke('val')
            .then(somevalue => {
                if (somevalue == "0") {
                    cy.get('[data-index="is_active"] > .admin__field-control > .admin__actions-switch > .admin__actions-switch-label').click()
                }
            })
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[2]/div[2]/fieldset/div[7]/div[2]/input").clear().type("fixed")
        cy.get('[data-index="actions"] > .fieldset-wrapper-title > .admin__collapsible-title').click()
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[1]/div[2]/select").select("Fixed amount discount")
        cy.xpath("/html/body/div[2]/main/div[2]/div/div/div/div[2]/div[4]/div[2]/fieldset/div[2]/div[2]/input").clear().type("10")
        cy.get('body').then(($p) => {
            if ($p.find(".rule-param-remove > .v-middle").length) {
                cy.get('.rule-param-remove > .v-middle').click()
            }
        })
        cy.get('#save').click()
    }

    change_currency_to_EUR_for_iDEAL() {
        cy.get('#menu-magento-backend-stores > [onclick="return false;"]').click()
        cy.get('.item-system-config > a').click()
        cy.contains('Currency Setup').click()
        cy.get('#currency_options-head').then(($cr) => {
            if ($cr.hasClass('open')) {
                cy.get('#currency_options_base').wait(2000).select('Euro')
                cy.get('#currency_options_default').wait(2000).select('Euro')
                cy.get('#currency_options_allow').wait(2000).select('Euro')
                cy.get('#save').click().wait(2000)
            } else {
                cy.get('#currency_options-head').click()
                cy.get('#currency_options_base').wait(2000).select('Euro')
                cy.get('#currency_options_default').wait(2000).select('Euro')
                cy.get('#currency_options_allow').wait(2000).select('Euro')
                cy.get('#save').click().wait(2000)
            }
        })
        //Flush cache
        cy.get('#menu-magento-backend-system > [onclick="return false;"]').scrollIntoView().click()
        cy.get('.item-system-cache > a').click()
        cy.get('#flush_magento').click()
        cy.get('#flush_system > span').click()
        cy.get('.action-primary > span').click().wait(60000)
    }

    ideal_payment(iDEAl_EUR_TERMINAL) {
        cy.contains(iDEAl_EUR_TERMINAL).click({ force: true })
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(2000)
        cy.get('#idealIssuer').select('AltaPay test issuer 1')
        cy.get('#pensioPaymentIdealSubmitButton').click()
        cy.get('[type="text"]').type('shahbaz.anjum123-facilitator@gmail.com')
        cy.get('[type="password"]').type('Altapay@12345')
        cy.get('#SignInButton').click()
        cy.get(':nth-child(3) > #successSubmit').click().wait(1000)

    }

    ideal_refund() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click()
        cy.get('.item-sales-order > a').click().wait(9000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_creditmemo > span').click()
        cy.contains('Refund Offline').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    change_currency_to_DKK() {
        cy.get('#menu-magento-backend-stores > [onclick="return false;"]').click()
        cy.get('.item-system-config > a').click()
        cy.get('#save').click()
        cy.contains('Currency Setup').click()
        cy.get('#currency_options-head').then(($cr) => {
            if ($cr.hasClass('open')) {
                cy.get('#currency_options_base').select('Danish Krone')
                cy.get('#currency_options_default').select('Danish Krone')
                cy.get('#currency_options_allow').select('Danish Krone')
                cy.get('#save').click().wait(2000)
            } else {
                cy.get('#currency_options-head').click()
                cy.get('#currency_options_base').select('Danish Krone')
                cy.get('#currency_options_default').select('Danish Krone')
                cy.get('#currency_options_allow').select('Danish Krone')
                cy.get('#save').click().wait(2000)
            }
        })
      
        //Flush cache
        cy.get('#menu-magento-backend-system > [onclick="return false;"]').scrollIntoView().click()
        cy.get('.item-system-cache > a').click()
        cy.get('#flush_magento').click()
        cy.get('#flush_system > span').click()
        cy.get('.action-primary > span').click().wait(60000)
    }
}

export default Order
