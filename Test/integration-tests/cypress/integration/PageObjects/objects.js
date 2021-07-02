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
        cy.get('[id^=qty]').clear()
        cy.get('[id^=qty]').type('3')
        cy.wait(2000)
        cy.contains('Add to Cart').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click()
        cy.get('.checkout-methods-items > :nth-child(1) > .action').click().wait(5000)
        cy.get('#customer-email-fieldset > .required > .control > #customer-email').type('demo@example.com')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[1]/div/input').type('Testperson-dk')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[2]/div/input').type('Testperson-dk')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[4]/div/select').select('Denmark')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/fieldset/div/div[1]/div/input').type('Sæffleberggate 56,1 mf')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[7]/div/input').type('Varde')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[8]/div/input').type('6800')
        cy.xpath('/html/body/div[2]/main/div[2]/div/div[2]/div[4]/ol/li[1]/div[2]/form[2]/div/div[9]/div/input').type('20123456')
        cy.get(':nth-child(2) > :nth-child(1) > .radio').click()
        cy.wait(1000)
        cy.get('.button').click().wait(3000)
    }

    cc_payment(){
        cy.fixture('config').then((admin)=>{
            cy.contains(admin.CC_TERMINAL_NAME).click({force: true})
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
        })
        }

    klarna_payment(){

        cy.fixture('config').then((admin)=>{
            cy.contains(admin.KLARNA_DKK_TERMINAL_NAME).click({force: true})
            
        cy.wait(3000)
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click()
        cy.get('[id=submitbutton]').click().wait(3000)
        cy.wait(3000)
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
            }
            )
        })   
    }

    admin()
    {
        cy.fixture('config').then((admin)=>{
            cy.visit(admin.adminURL)
            cy.get('#username').type(admin.adminUsername)
            cy.get('#login').type(admin.adminPass)
            cy.get('.action-login').click().wait(2000)
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
            cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')
            cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').click()
            cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').click()
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
            cy.contains('Push It Messenger Bag').click({force: true})
            //subscription check start

            cy.get('body').then(($a) => { 
                if ($a.find('[for="radio_subscribe_product"]').length) {
                    cy.get('[for="radio_subscribe_product"]')
                    .click({force:true})

            cy.get('[for="radio_subscribe_product"]').wait(1000).click()
            cy.contains('Add to Cart').click()
            cy.wait(2000)
            cy.get('.message-success > div > a').click()
            cy.wait(5000)
            cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
            cy.wait(3000)

            cy.get('.button').click().wait(3000)
            
            //Subscription payment
            
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
            }
        )
        
            //Login dmin
            cy.fixture('config').then((admin)=>{
                cy.visit(admin.adminURL)
                cy.get('#username').type(admin.adminUsername)
                cy.get('#login').type(admin.adminPass)
                cy.get('.action-login').click().wait(2000)
                })

            //Capture payment
            cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
            cy.get('.item-sales-order > a').click().wait(5000)
            cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
            cy.get('#order_invoice > span').wait(5000).click()
            cy.wait(5000)
            cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
            cy.wait(5000)
            cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')

            
        } 
        
        )}
    
                else {
                        
                    cy.log('Subscription Process not found')   

                }
})

            //subscription check end

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
                cy.get('#city').type('Varde')
                cy.get('#zip').type('6800')
                cy.get('#form-validate > .actions-toolbar > div.primary > .action').click()
        }
    
        }  


export default Order