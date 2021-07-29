require('cypress-xpath')

class Order
{
    clrcookies(){
        cy.clearCookies()
    }
    visit()
    {
        cy.fixture('config').then((url)=>{
        cy.visit(url.shopURL)    
        })    
    }


    addproduct()
    {
        cy.contains('Fusion Backpack').wait(3000).click()
        cy.contains('Add to Cart').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click()
        cy.get('.checkout-methods-items > :nth-child(1) > .action').wait(5000).click().wait(5000)
        cy.get('#customer-email-fieldset > .required > .control > #customer-email').type('demo@example.com')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[1]/div/input').type('Testperson-dk')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[2]/div/input').type('Testperson-dk')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[4]/div/select').select('Denmark')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/fieldset/div/div[1]/div/input').type('Sæffleberggate 56,1 mf')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[7]/div/input').type('Varde')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[8]/div/input').type('6800')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[9]/div/input').type('20123456')
        cy.get('.radio').click({ multiple: true })
        cy.wait(1000)
        cy.get('.button').click().wait(5000)
        
    }

    cc_payment(CC_TERMINAL_NAME)
    {
        
        cy.contains(CC_TERMINAL_NAME).click({force: true})
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(2000)
        cy.get('#creditCardNumberInput').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(3000)
        cy.get('.base').should('have.text', 'Thank you for your purchase!')
        cy.get('.checkout-success > :nth-child(1) > span').then(($btn) => {

            const txt = $btn.text()
            cy.log(txt)
            }
            )
       
    }

    klarna_payment(KLARNA_DKK_TERMINAL_NAME)
    {

            cy.contains(KLARNA_DKK_TERMINAL_NAME).click({force: true})
            
        cy.wait(3000)
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(5000)
        cy.get('[id=submitbutton]').click().wait(5000)
        cy.wait(5000)
        cy.get('[id=klarna-pay-later-fullscreen]').then(function($iFrame){
            const mobileNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-phone-number]')
            cy.wrap(mobileNum).type('(452) 012-3456')
            const personalNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-national-identification-number]')
            cy.wrap(personalNum).type('1012201234')
            const submit = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-continue-button]')
            cy.wrap(submit).click()
            
        })
        
        cy.wait(3000)
        cy.get('.base').should('have.text', 'Thank you for your purchase!')
        
        cy.get('.checkout-success > :nth-child(1) > span').then(($btn) => {

            const txt = $btn.text()
            cy.log(txt)
        })
    }

    admin()
    {
        cy.fixture('config').then((conf)=>{
            cy.visit(conf.adminURL).wait(5000)
            cy.get('#username').type(conf.adminUsername)
            cy.get('#login').type(conf.adminPass)
            cy.get('.action-login').click().wait(5000)
            cy.get('body').then(($p) => {
                if($p.find(".action-secondary").length){
                        cy.get('.action-secondary').click()
                }
            })
        })

    }

    capture()
    {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_invoice > span').wait(5000).click()
        cy.wait(6000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
        cy.wait(6000)

    }

    refund(){

        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('#credit-memo > span').click()
        cy.wait(2000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]/span').click()
        cy.wait(1000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }


    subscription_product()
    {
        cy.get('img').click()
        cy.contains('Push It Messenger Bag').click({force: true}).wait(5000)

    }
    subscrition_check()
    {

        cy.get('[for="radio_subscribe_product"]').wait(1000).click()
        cy.contains('Add to Cart').click()
        cy.wait(2000)
        cy.get('.message-success > div > a').click()
        cy.wait(5000)
        cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
        cy.wait(3000)

        cy.get('.button').click().wait(3000)

    }

            
    //Subscription payment
    subscription_payment()
    {
                
        cy.fixture('config').then((admin)=>{
            cy.contains(admin.SUBSCRIPTION_TERMINAL_NAME).click({force: true})

            

        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(3000)
        cy.get('#creditCardNumberInput').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(6000)

        cy.get('.base').should('have.text', 'Thank you for your purchase!')

        cy.get('#maincontent > div.columns > div > div.checkout-success > p:nth-child(1) > a > strong').then(($btn) => {

        const txt = $btn.text()
        cy.log(txt)
        })
            
        })
    }

        
    signin(){

        cy.contains('Create an Account').click()
        cy.get('#firstname').type('Testperson-dk')
        cy.get('#lastname').type('Testperson-dk')

            function generateNewUsername() {
                let text = "";
                let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"

                for(let i = 0; i < 10; i++) 
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
    
    partial_capture()
    {

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

    addpartial_product()
    {
        
        cy.contains('Push It Messenger Bag').wait(3000).click()
        cy.contains('Add to Cart').click()
        cy.wait(3000)
    }

    partial_refund()
    {
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('#credit-memo > span').click()
        cy.wait(2000)
        cy.get('.even > :nth-child(1) > .col-refund > .input-text').clear().type('0')
        cy.get('.col-refund > span').click()
        cy.contains("Update Qty's").click().wait(2000)
        cy.xpath('/html/body/div[3]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]').click()
        cy.wait(1000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }
        
    release_payment()
    {

        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order-view-cancel-button').click()
        cy.get('.confirm > .modal-inner-wrap > .modal-footer > .action-primary').click()

    }

}

export default Order