# Changelog
All notable changes to this project will be documented in this file.

## [3.9.8]
### Added
- Support Amasty Mass Order Actions functionlaity with Altapay extension.

## [3.9.7]
### Fixed
- Re-style callback redirect page.

## [3.9.6]
### Fixed
- Improve the handling of Apple Pay responses.
- Change the order status from Pending to Cancelled if the Apple Pay payment fails.

## [3.9.5]
### Added
- Add support for PHP 8.2
### Fixed
- Fix: Round off unit price in order line to 3 decimal digits for tax exclusive configurations.
- Log Apple Pay error in the order history.

## [3.9.4]
### Added
- Support for Mageplaza One-Step Checkout extension.
### Fixed
- Fix: Issue with multiple terms and conditions being enabled.

## [3.9.3]
### Added
- Add support for fixed price bundle products.
- Configure terminal logo automatically.

## [3.9.2]
### Fixed
- Release stock in the event of a browser back operation being executed.
- Fix: `Current customer does not have an active cart` when redirecting to the payment page.

## [3.9.1]
### Added
- Add terminal logo for Payconiq.
### Fixed
- Restore cart items in case of Apple Pay cancel operation.
- Fix: Round off unit price in order line to 3 decimal digits.
- Fix: Getting deprecated error when paying without phone number.

## [3.9.0]
### Added
- Add support for `Fire Checkout - One Page Checkout` extension.

## [3.8.9]
### Added
- Add support for Trustly payment method.

## [3.8.8]
### Added
- Add support for SEPA payment method.
- Populate the cardholder name in the payment form based on the billing information

### Fixed
- Do not release the order in case of an error and when the reservation amount is greater than 0.
- Fix: Missing cart dependency from the ApplePayOrder class

## [3.8.7]
### Added
- Add support for Twint payment method.

## [3.8.6]
### Added
- Add Apple Pay support for oneStepCheckout extension.

### Fixed
- Fix: Apple Pay amount mis-match issue with multi-store website.

## [3.8.5]
### Fixed
- Remove discount line if discount amount is 0 from the order summary screen.

## [3.8.4]
### Fixed
- Fix: Translation issue with email template in a multi-store website.

## [3.8.3]
### Added
- Add translations for the "Pay by link" email template.
- Add an option to change the logo for the checkout form page.
- Add configuration to display prices on the order summary, including tax.

### Fixed
- Fix styling issues for the order summary section.

## [3.8.2]
### Added
- Show notification for a plugin upgrade in the admin interface when a new extension version is available.

## [3.8.1]
### Added
- Fix: Do not release the payment when receiving a fail callback if the payment is in a successful state

## [3.8.0]
### Added
- Fix: Multi-currency issue with Pay by link payment form.
- Fix: Billing address selection missing from terminal slot 6 to 10.
- Fix: Payment form link in the email is not working.

## [3.7.9]
### Added
- Support Fooman OrderFees functionlaity with Altapay extension.
- Run the cron on an hourly basis when the auto-cancel functionality is enabled.
- Remove daily, weekly and monthly cron scheduler to minimize the complexity
- Add configuration to exclude admin orders from the auto-cancel cron scheduler.

## [3.7.8]
### Added
- Allow to disable Online Refunds through configuration
- Support defining time for auto-cancelling orders
- Fix minor styling issues on the checkout page.

## [3.7.7]
### Added
- Support edit functionality from admin panel.

## [3.7.6]
### Added
- Support re-order functionality from admin panel

## [3.7.5]
### Fixed
- Add support for Open Banking (Using Finshark)

## [3.7.4]
### Fixed
- Fix typo from the wiki doc.
- Update `Payment page layout` dropdown options. 

## [3.7.3]
### Added
- Add payment page `Custom Layout` option, which is independent of the store styling.
### Fixed
- Order status not updating when plugin receives callback notification only.

## [3.7.2]
### Added
- Add terminal logo for Bancontact, Przelewy24 & Bank payments.
- Add new Klarna's main logo (pink).
- Add horizontal variation for MobilePay & Swish terminal logos.
- Updated and resized the checkout terminal logos.
### Fixed
- Fix exception when getOrderId returns a null value on the order summary page.

## [3.7.1]
### Added
- Make the checkout form style option available to all payment forms.

## [3.7.0]
### Added
- Add option to select charged currency for AltaPay payment processing.
### Fixed
- Fix: Amount mismatch issue with Apple Pay.

## [3.6.13]
### Added
- Add a new design option with a modern look for the Credit Card form.

## [3.6.12]
### Fixed
- Fix: Order cancelled/released if plugin receives fail notification with the same order id but a different transaction id.

## [3.6.11]
### Added
-  Implement checksum functionality

## [3.6.10]
### Fixed
-  Fix - Missing refund callback in case of auto-capture enable

## [3.6.9]
### Fixed
-  Resolve plugin compatibility issue with php8.2

## [3.6.8]
### Fixed
-  Add PayPal to terminal logos.

## [3.6.7]
### Fixed
-  Supports API changes from 20230412
-  Update API-PHP version to enforce the right HTTP methods on all API endpoints.

## [3.6.5]
### Fixed
-  Use Data Patch instead of UpgradeData scripts, as it is obsolete.

## [3.6.4]
### Added
-  Improve error logging for frontend.

## [3.6.3]
### Fixed
Fix: undefined variable maskedPan

## [3.6.2]
### Fixed
- Fix saved card redirection issue for `Unscheduled Type` charge.

## [3.6.1]
### Added
- Show the last four digits instead of masked PAN on the card selection.

## [3.6.0]
### Added
- Add configuration to enable the handling of fraudulent payments.

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