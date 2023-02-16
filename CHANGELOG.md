# Changelog
All notable changes to this project will be documented in this file.

## [3.5.9]
### Added
- Pass agreement parameters in chargeSubscription in case agreement[type] is unsceduled.
- Add configuration field and set the possible value of agreement[unscheduled_type].

### Fixed 
- Fix: Order confirmation email is not sent when paying via Apple Pay
- Fix: Compilation issue caused by new parameters added to varchar field type.

### Fixed 
- Fix: An incorrect amount is transmitted to the payment gateway when using the ApplePay payment method

## [3.5.8]
### Fixed 
- Fix: Reconciliation identifiers are shown on the wrong orders.

## [3.5.7]
### Fixed 
- Fix: The default value for "isReservation" isn't defined.

## [3.5.6]
### Added 
- Add option to select custom email template for pay by link
- Allow multiple quantities for subscription product

## [3.5.5]
### Fixed 
- Fix: Compilation issues with Php8.1

## [3.5.4]
### Fixed 
- Fix: Update altapay/api-php version in composer

## [3.5.3]
### Fixed 
- Fix: can't checkout when multiple T&C are enabled
- Fix: verify_card payment issue with the auto-capture functionality

## [3.5.2]
### Added
- Set a different reconciliation identifier for each transaction
### Fixed 
- Fix: Refund payment issue for subscription payments

## [3.5.1]
### Added
- Add support to allow up to 10 payment methods

## [3.5.0]
### Added
- Add support for auto-capture functionality with the subscriptions
- Add logos for Apple Pay, Swish and Vipps payment methods

## [3.4.9]
### Added
- Add support for saving credit card for later use without CVV/CVC
### Fixed
- Handle multiple notification callback to avoid auto-release

## [3.4.8]
### Added
- Add a payment link in the order information grid
- Add support for payment reconciliation
### Fixed
- Fix the "refund offline" trigger a refund callback to the gateway

## [3.4.7]
### Added
- Add support for new 'Agreements Engine' parameters
### Fixed
- Fix duplicate callback being sent to the gateway

## [3.4.6]
### Fixed
- Fix blank checkout in case the plugin is not configured
- Fix critical logs handling 

## [3.4.5]
### Added
- Support for pay by link

## [3.4.4]
### Fixed
- Fix refund callback does not trigger in case of IDEAL payment

## [3.4.3]
### Added
- Add text field under terminal name for custom message

### Changed
- Resize payment method logos to improve the page load time

## [3.4.2]
### Added
- Add configuration field for "ApplePay popup label"

## [3.4.1]
### Added
- Support multiple logos/icons option for terminals.

## [3.4.0]
### Fixed
- Fix compilation failed due to duplicate "Transaction" class

## [3.3.9]
### Fixed
- Fix remove internal classes and add php-api dependency
### Added
- Add PHP support from 7.0 to 8.1

## [3.3.8]
### Fixed
- Fix release stock qty on order cancellation

## [3.3.7]
### Fixed
- Fix order status set to "pending" on "incomplete" response
- Fix cookies restriction notice is not functional
- Fix missing CardInformation parameters from the Transaction class

## [3.3.6]
### Added
- Support tax exclusive configurations.

## [3.3.5]
### Added
- Add support for Apple Pay

## [3.3.4]
### Fixed
- Fix incorrect discount calculation 

## [3.3.3]
### Fixed
- Fix order status set to "closed" for "Vipps" payment method

## [3.3.2]
### Fixed
- Fix order status set to "closed" despite the orders being in a pre-auth state.

## [3.3.1]
### Fixed
- Fix canceled order qty from item grid is missing

## [3.3.0]
### Fixed
- Fix stock quantity calculation issue

### Added
- Add configurations section to setup cron scheduler to change the status of the pending order to cancel

## [3.2.9]
### Fixed
- Fix handle empty synch button response

## [3.2.8]
### Fixed
- Fix cancel order if payment_status is "released" in notification callback

## [3.2.7]
### Added
- Add a button to trigger the sync of the terminals with the gateway

## [3.2.6]
### Fixed
- Fix: Saved credit cards grid styling for mobile view

### Changed
- Migrate install/upgrade scripts to declarative schema

## [3.2.5]
### Fixed
- Fix success page rendering issue when placing an order in incognito mode with the MobilePay

## [3.2.4]
### Fixed
- Fix product stock not updating when order status change from cancel to processing

## [3.2.3]
### Fixed
- Fix cancel order issues when there is no transaction

## [3.2.2]
### Fixed
- Fix order failing issue when applying a fixed discount on the cart

## [3.2.1]
### Fixed
- Fix compilation issue due to a missing file path

## [3.2.0]
### Added
- Add support when cart and catalog rules are applied simultaneously
- Make text "No saved credit cards" translatable

## [3.1.9]
### Added
- Support multi-language for order summary section in form rendering

## [3.1.8]
### Added
- Support AutoCapture functionality with subscription product

## [3.1.7]
### Added
- Support subscription product with Amasty plugin

## [3.1.6]
### Added
- Added version node in composer file

## [3.1.5]
### Added
- Added support for terminal sorting

## [3.1.4]
### Changed
- Remove deprecated validation file from the bash script
### Fixed
- Remove unnecessary files while creating zip package

## [3.1.3]
### Fixed
- Remove payment terminal shown upon editing order from backend
- Fix "Could not load HTML" issue cause by X-Magento-Tags
      
## [3.1.2]
### Added
- Add a shell script that creates the zip folder
- Redirect failed orders to cart details page

## [3.1.1]
### Fixed
- Updated shipping template with the minor bug fix

## [3.1.0]
### Added
- Supporting fixed product tax configurations

### Changed
- Rebranding from Valitor to Altapay

### Fixed
- Supporting fixed product tax configurations
- Fixed order creation issue with free shipping
- Fixed translation issue for status code

## [3.0.0]
### Added
- Added plugin disclaimer
- Code refactored according to latest coding standards
- Added support for Klarna Payments (Klarna reintegration) and credit card token
- Added the option of choosing a logo for each payment method
- Added new parameters, according to the payment gateway Klarna Payments updates, for the following:
    - Create payment request
    - Capture and refund
- Added support for AVS
- Added support for fixed amount and Buy X get Y free discount type

### Fixed
- Discount applied to shipping not sent to the payment gateway accordingly
- Order details dependent on the current tax configuration rather than the one at the time when order was placed

## [2.2.0]
### Added
- Added a fix in relation to a bug in Magento core source code
 - Completed the rebranding changes
- Revamped orderlines for capture and refund calls
- Added support for bundle product and multiple tax rules

### Fixed
- Failed order when coupon code applied only to shipping
- Duplicated confirmation email sent when e-payments
- Rounding mismatch issue in compensation amounts

## [2.1.0]
### Added
- A new batch of improvements and bug fixes

## [2.0.0]
### Fixed
- Major improvements and bug fixes

## [1.1.4]
### Fixed
- Fixed the symfony dependency: either one from the next list will be used, according to the Magento version: 2.6, 3.0 or 4.0+

## [1.1.3]
### Added
- Added a check before a quote is restored

### Fixed
- Fixed the authorization from the checkout section

## [1.1.2]
### Fixed
- Internal reference updates

## [1.1.1]
### Fixed
- Replaced the pop up messages with regular ones

## [1.1.0]
### Added
- Added support for PHP 5.5 and 5.6

### Changed
- Updated the PHP client API

## [1.0.1]
### Added
- First release
