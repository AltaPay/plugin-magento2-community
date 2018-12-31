# AltaPay Magento2 extension

AltaPay has made it much easier for you as merchant/developer to receive secure payments in your Magento2
web shop.


== Change log ==

** Version 0.3.1

    * Bug fix: cancel the order if the consumer moves away from the payment form by using the back button in the browser

** Version 0.3.0
    
    * Improvement: payment form with the order details 
    * Bug fix: empty cart if consumer uses the back button from the payment form

** Version 0.2.1

    * Bug fix: 
            - Terms and Condition checkbox in checkout page
            - Order status, before payment, set according to the setting from the store

** Version 0.2.0

    * Improvements: 
            - update the order with the correct status and state in accordance to the payment gateway response 
            - use StoreScope on all connections to the payment gateway
            - add Enable option for terminals on store level

** Version 0.1.11

    * Bug fix: Support for scope

** Version 0.1.10

    * Improvements: 
            - orderLines (including taxAmount) added in the Refund request
            - taxAmount added to Capture request


** Version 0.1.9

    * Bug fixes: error message not shown in case of a payment gateway error
    * Client library updated: new element in the XML response for CreatePaymentRequest

** Version 0.1.8

    * Bug fix: amount type set to float
    
** Version 0.1.7

    * Bug fix: unit price and "handling" GoodsType
    
** Version 0.1.6

    * Support for tax information in the order lines

