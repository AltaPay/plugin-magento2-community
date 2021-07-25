# Altapay for Magento2 community

Altapay has made it much easier for you as merchant/developer to receive secure payments in your Magento2.3.x web shop.

**Note, If you are getting 403 forbidden error in "Magento Commerce Cloud". It can be caused by "Fastly", which blocks our callbacks. In this case, please contact fastly support.**

== Change log ==

** Version 3.2.0

    * Improvements:
        - Add support when cart and catalog rules are applied simultaneously
        - Make text "No saved credit cards" translatable

** Version 3.1.9

    * Improvements:
        - Support multi-language for order summary section in form rendering

** Version 3.1.8

    * Improvements:
        - Support AutoCapture functionality with subscription product

** Version 3.1.7

    * Improvements:
        - Support subscription product with Amasty plugin

** Version 3.1.6

    * Improvements:
        - Added version node in composer file

** Version 3.1.5

    * Improvements:
        - Added support for terminal sorting

** Version 3.1.4

    * Bug fixes:
        - Remove deprecated validation file from the bash script
        - Remove unnecessary files while creating zip package

** Version 3.1.3

    * Bug fixes:
        - Remove payment terminal shown upon editing order from backend
        - Fix "Could not load HTML" issue cause by X-Magento-Tags
      
** Version 3.1.2

    * Improvements:
        - Add a shell script that creates the zip folder
        - Redirect failed orders to cart details page
        
** Version 3.1.1

    * Bug fixes:
        - Updated shipping template with the minor bug fix

** Version 3.1.0

    * Improvements:
        - Rebranding from Valitor to Altapay
        - Supporting fixed product tax configurations
    * Bug fixes:
        - Fixed order creation issue with free shipping
        - Fixed translation issue for status code

** Version 3.0.0

    * Improvements:
        - Added plugin disclaimer
        - Code refactored according to latest coding standards
        - Added support for Klarna Payments (Klarna reintegration) and credit card token
        - Added the option of choosing a logo for each payment method
        - Added new parameters, according to the payment gateway Klarna Payments updates, for the following:
            - Create payment request
            - Capture and refund
        - Added support for AVS
        - Added support for fixed amount and Buy X get Y free discount type
    * Bug fixes:
        - Discount applied to shipping not sent to the payment gateway accordingly
        - Order details dependent on the current tax configuration rather than the one at the time when order was placed

** Version 2.2.0

    * Improvements:
            - Added a fix in relation to a bug in Magento core source code
            - Completed the rebranding changes
            - Revamped orderlines for capture and refund calls
            - Added support for bundle product and multiple tax rules
    * Bug fixtures:
            - Failed order when coupon code applied only to shipping
            - Duplicated confirmation email sent when e-payments
            - Rounding mismatch issue in compensation amounts

** Version 2.1.0

    * A new batch of improvements and bug fixes

** Version 2.0.0

    * Major improvements and bug fixes

** Version 1.1.4
    
    * Fixed the symfony dependency: either one from the next list will be used, according to the Magento version: 2.6, 3.0 or 4.0+

** Version 1.1.3

    * Fixed the authorization from the checkout section
    * Added a check before a quote is restored

** Version 1.1.2

    * Internal reference updates

** Version 1.1.1

    * Replaced the pop up messages with regular ones

** Version 1.1.0

    * Added support for PHP 5.5 and 5.6
    * Updated the PHP client API

** Version 1.0.1
    
    * First release


# How to run cypress test successfully in your environment 

## Prerequisites: 

1) Magento2 and default dummy data should be installed on publically accessible URL
2) Cypress should be installed
3) For subscription test, "Push It Messenger Bag" product should be configured as Subscription product

## Information: 

i) These tests are for only Credit Card, Klarna DKK and AltaPay Subscription (Credit Card for Subscription)
ii) In case, you dont want to test any of the above mentioned payment methods, please leave it blank in the config file. i.e "CC_TERMINAL_NAME":""

## Steps: 

1) Install dependencies `npm i`

2) Update "cypress/fixtures/config.json" 

3) Execute `./node_modules/.bin/cypress run` in the terminal to run all the tests