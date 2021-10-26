import Order from '../PageObjects/objects'

describe('Magento2', function () {

    it('CC full capture and refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
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
        if ($body.text().includes('€')) {
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


it('Subscription', function () {
    const ord = new Order()
    ord.clrcookies()
    ord.visit()
    ord.signin()
    ord.subscription_product()
    cy.get('body').then(($a) => {
        if ($a.find("label:contains('Subscribe to this product.')").length) {
            cy.contains('Subscribe to this product.')
                .click({ force: true })
            ord.subscrition_check()
            ord.subscription_payment()
            ord.admin()
            ord.capture()
        }
        else {
            cy.log('Subscription product not found')
            this.skip()
        }

    })
})

it('CC partial capture', function () {
    const ord = new Order()
    ord.clrcookies()
    ord.visit()
    cy.get('body').then(($body) => {
        if ($body.text().includes('€')) {
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
        if ($body.text().includes('€')) {
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
        if ($body.text().includes('€')) {
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
        if ($body.text().includes('€')) {
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
            if ($body.text().includes('€')) {
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
            if ($body.text().includes('€')) {
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

    it('iDEAL Payment', function () {
        const ord = new Order()
        ord.visit()
        cy.get('body').then(($body) => {

            if ($body.text().includes('DKK')) {
                ord.admin()
                ord.change_currency_to_EUR_for_iDEAL()
            } 
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.iDEAl_EUR_TERMINAL != "") {
                    cy.get('body').wait(3000).then(($a) => {
                        if ($a.find("label:contains('" + admin.iDEAl_EUR_TERMINAL + "')").length) {
                            ord.ideal_payment(admin.iDEAl_EUR_TERMINAL)
                            ord.admin()
                            ord.ideal_refund()
                        } else {
                            cy.log(admin.iDEAl_EUR_TERMINAL + ' not found in page')
                            this.skip()
                        }

                    })
                }
                else {
                    cy.log('iDEAl_EUR_TERMINAL skipped')
                    this.skip()
                }
            })
        })
    })
  
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
