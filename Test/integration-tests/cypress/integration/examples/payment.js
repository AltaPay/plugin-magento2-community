import Order from '../PageObjects/objects'




describe ('Magento2', function(){

    it('CC Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        ord.cc_payment()
        ord.admin()
        ord.capture()
    })

    it('Klarna Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        ord.klarna_payment()
        ord.admin()
        ord.capture()
    })



})