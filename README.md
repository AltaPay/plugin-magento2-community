# AltaPay for Magento2 Community

AltaPay has made it much easier for you as merchant/developer to receive secure payments in your Magento2 web shop.

[![Latest Stable Version](http://poser.pugx.org/altapay/magento2-community/v)](https://packagist.org/packages/altapay/magento2-community)
[![Total Downloads](http://poser.pugx.org/altapay/magento2-community/downloads)](https://packagist.org/packages/altapay/magento2-community)
[![License](http://poser.pugx.org/altapay/magento2-community/license)](https://packagist.org/packages/altapay/magento2-community)

## Supported Payment Methods & Functionalities
<table>
<tr><td>

| Functionalities	        | Support       |
| :------------------------ | :-----------: |
| Reservation               | &check;       |
| Capture                   | &check;       |
| Instant Capture           | &check;       |
| Multi Capture             | &check;       |
| Recurring / Subscription  | &check;       |
| Release                   | &check;       |
| Refund                    | &check;       |
| Multi Refund              | &check;       |
| 3D Secure                 | &check;       |
| Fraud prevention (other)  | &check;       |
| Reconciliation            | &check;       |
| MO/TO                     | &cross;       |

</td><td valign="top">

| Payment Methods	  | Support       |
| ------------------- | :-----------: |
| Card                | &check;       |
| Invoice             | &check;       |
| ePayments           | &check;       |
| Bank-to-bank        | &check;       |
| Interbank           | &check;       |
| Cash Wallet         | &check;       |
| Mobile Wallet       | &check;       |

</td></tr> </table>


**Note, If you are getting 403 forbidden error in "Magento Commerce Cloud". It can be caused by "Fastly", which blocks our callbacks. In this case, please contact fastly support.**

## Compatibility
- Magento 2.3 and above

    For Magento 2.2 and below please see [AltaPay plugin for Magento2.2](https://github.com/AltaPay/plugin-magento2)

## Installation
Run the following commands in Magento 2 root folder:

    composer require altapay/magento2-community
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    php bin/magento setup:static-content:deploy

## Additional modules

We have created several supplementary modules to enhance the features of AltaPay for Magento2 Community.

- [AltaPay for Magento 2 Recurring Payments](https://github.com/AltaPay/plugin-magento2-subscriptions) - Enable subscription and recurring payment processing on your Magento 2 online store. This module ensures seamless integration with Amasty Subscriptions & Recurring Payments.
- [Fooman Order Fees extension integration](https://github.com/AltaPay/plugin-magento2-fooman) - Charge Magento customers extra fees and order upgrades at checkout time.

## Supported Extensions

The AltaPay Payment extension has been tested and confirmed to be compatible with the following modules:

- [Amasty Subscriptions & Recurring Payments](https://amasty.com/subscriptions-recurring-payments-for-magento-2.html)
- [Amasty One Step Checkout Pro](https://amasty.com/one-step-checkout-for-magento-2.html)
- [OneStepCheckout](https://www.onestepcheckout.com/magento-2)
- [Fooman Order Fees](https://fooman.com/magento-extension-order-fees-m2.html)
- [Fire Checkout - One Page Checkout](https://www.firecheckout.net/)
- [Mageplaza OneStepCheckout](https://www.mageplaza.com/magento-2-one-step-checkout-extension/)

## How to run cypress tests

> **Prerequisites**
> 
> - Magento 2 with default theme (Luma) & sample data
> - For subscription test, "Argus All-Weather Tank" product should be configured as subscription product

### Steps:

* Navigate to `tests/integration-test`
* Install cypress by executing 

        npm i
        
* Update `cypress/fixtures/config.json`
* Run cypress

        ./node_modules/.bin/cypress open

## Changelog

See [Changelog](CHANGELOG.md) for all the release notes.

## License

Distributed under the MIT License. See [LICENSE](LICENSE) for more information.

## Documentation

For more details please see [docs](https://github.com/AltaPay/plugin-magento2-community/wiki)
