# Changelog
All notable changes to this project will be documented in this file.

## [3.4.1]

**Improvement**
- Support multiple logo/icon for terminal

## [3.4.0]

**Fixes**
- Fix: Compilation failed due to duplicate "Transaction" class

## [3.3.9]

**Fixes**
- Fix: Remove internal classes and add php-api dependency
- Fix: Add PHP support from 7.0 to 8.1

## [3.3.8]

**Fixes**
- Fix: Release stock qty on order cancellation

## [3.3.7]

**Fixes**
- Fix: Order status set to "pending" on "incomplete" response
- Fix: Cookies restriction notice is not functional
- Fix: Added CardInformation parameters for Transaction class

## [3.3.6]

**Improvement**
- Support tax exclusive configurations.

## [3.3.5]

**Improvement**
- Add support for Apple Pay

## [3.3.4]

**Fixes**
- Fix: Incorrect discount calculation 

## [3.3.3]

**Fixes**
- Fix: Order status set to "closed" for "Vipps" payment method

## [3.3.2]

**Fixes**
- Fix: Order status set to "closed" despite the orders being in a pre-auth state.

## [3.3.1]

**Fixes**
- Fix: Canceled order qty from item grid is missing

## [3.3.0]

**Fixes**
- Fix: Stock quantity calculation issue

**Improvements**
- Add configurations section to setup cron scheduler to change the status of the pending order to cancel

## [3.2.9]

**Fixes**
- Fix: Handle empty synch button response

## [3.2.8]

**Fixes**
- Fix: Cancel order if payment_status is "released" in notification callback

## [3.2.7]

**Improvements**
- Add a button to trigger the sync of the terminals with the gateway

## [3.2.6]

**Fixes**
- Fix: Saved credit cards grid styling for mobile view

**Improvements**
- Migrate install/upgrade scripts to declarative schema

## [3.2.5]

**Fixes**
- Fix: Success page rendering issue when placing an order in incognito mode with the MobilePay

## [3.2.4]

**Fixes**
- Fix: Product stock not updating when order status change from cancel to processing

## [3.2.3]
**Fixes**
- Fix: Cancel order issues when there is no transaction

## [3.2.2]
**Fixes**
- Fix: Order failing issue when applying a fixed discount on the cart

## [3.2.1]
**Fixes**
- Fix: Compilation issue due to a missing file path

## [3.2.0]
**Improvements**
- Add support when cart and catalog rules are applied simultaneously
- Make text "No saved credit cards" translatable

## [3.1.9]
**Improvements**
- Support multi-language for order summary section in form rendering

## [3.1.8]
**Improvements**
- Support AutoCapture functionality with subscription product

## [3.1.7]
**Improvements**
- Support subscription product with Amasty plugin

## [3.1.6]
**Improvements**
- Added version node in composer file

## [3.1.5]
**Improvements**
- Added support for terminal sorting

## [3.1.4]
**Fixes**
- Remove deprecated validation file from the bash script
- Remove unnecessary files while creating zip package

## [3.1.3]
**Fixes**
- Remove payment terminal shown upon editing order from backend
- Fix "Could not load HTML" issue cause by X-Magento-Tags
      
## [3.1.2]
**Improvements**
- Add a shell script that creates the zip folder
- Redirect failed orders to cart details page

## [3.1.1]
**Fixes**
- Updated shipping template with the minor bug fix

## [3.1.0]
**Improvements**
- Rebranding from Valitor to Altapay
- Supporting fixed product tax configurations

**Fixes**
- Fixed order creation issue with free shipping
- Fixed translation issue for status code

## [3.0.0]
**Improvements**
- Added plugin disclaimer
- Code refactored according to latest coding standards
- Added support for Klarna Payments (Klarna reintegration) and credit card token
- Added the option of choosing a logo for each payment method
- Added new parameters, according to the payment gateway Klarna Payments updates, for the following:
    - Create payment request
    - Capture and refund
- Added support for AVS
- Added support for fixed amount and Buy X get Y free discount type

**Fixes**
- Discount applied to shipping not sent to the payment gateway accordingly
- Order details dependent on the current tax configuration rather than the one at the time when order was placed

## [2.2.0]
**Improvements**
- Added a fix in relation to a bug in Magento core source code
 - Completed the rebranding changes
- Revamped orderlines for capture and refund calls
- Added support for bundle product and multiple tax rules

**Fixes**
- Failed order when coupon code applied only to shipping
- Duplicated confirmation email sent when e-payments
- Rounding mismatch issue in compensation amounts

## [2.1.0]
- A new batch of improvements and bug fixes

## [2.0.0]
- Major improvements and bug fixes

## [1.1.4]
- Fixed the symfony dependency: either one from the next list will be used, according to the Magento version: 2.6, 3.0 or 4.0+

## [1.1.3]
- Fixed the authorization from the checkout section
- Added a check before a quote is restored

## [1.1.2]
- Internal reference updates

## [1.1.1]
- Replaced the pop up messages with regular ones

## [1.1.0]
- Added support for PHP 5.5 and 5.6
- Updated the PHP client API

## [1.0.1]
- First release
