import Order from '../PageObjects/objects'

if(Cypress.env('runDiscountsTests')){

describe('Discounts', function () {

    it('Create cart percentage discount', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_cart_percent_discount()

    })

    it('Apply cart percentage discount with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_percent_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Apply cart percentage discount with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_percent_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
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

    it('Create cart fixed discount', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_cart_fixed_discount()

    })

    it('Apply cart fixed discount with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_fixed_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Apply cart fixed discount with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_fixed_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
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

    it('Create catalog percentage discount', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_catalog_percentage_discount()

    })

    it('Apply catalog percent discount with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Apply catalog percent discount with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
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

    it('Create catalog fixed discount', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_catalog_fixed_discount()

    })

    it('Apply catalog fixed discount with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Apply catalog fixed discount with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
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


    it('Multiple - Preparing Cart & Catalog Percentage discounts', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_catalog_percentage_discount()
        ord.create_cart_percentage_with_catalog()

    })

    it('Multiple - Applying Cart & Catalog Percentage discounts with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_percent_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Multiple - Applying Cart & Catalog Percentage discounts with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_percent_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
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

    it('Multiple - Preparing Cart Fixed & Catalog Percentage discounts', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.admin()
        ord.create_catalog_percentage_discount()
        ord.create_cart_fixed_with_catalog()

    })

    it('Multiple - Applying Cart Fixed & Catalog percentage discount with CC', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_fixed_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
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

    it('Multiple - Applying Cart Fixed & Catalog percentage discount with Klarna', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.apply_cart_fixed_discount()
        ord.complete_checkout()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').wait(3000).then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
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
}
