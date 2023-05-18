# AltaPay for Magento2 Community

AltaPay has made it much easier for you as merchant/developer to receive secure payments in your Magento2 web shop.

[![Latest Stable Version](http://poser.pugx.org/altapay/magento2-community/v)](https://packagist.org/packages/altapay/magento2-community)
[![Total Downloads](http://poser.pugx.org/altapay/magento2-community/downloads)](https://packagist.org/packages/altapay/magento2-community)
[![License](http://poser.pugx.org/altapay/magento2-community/license)](https://packagist.org/packages/altapay/magento2-community)

## Supported Payment Methods & Functionalities
<table>
<tr><td>

| Functionalities	        | Support       |
| ------------------------- | ------------- |
| Reservation               | &check;       |
| Capture                   | &check;       |
| Instant Capture           | &check;       |
| Multi Capture             | &check;       |
| Recurring / Subscription  | &check;       |
| Refund                    | &check;       |
| 3D Secure                 | &check;       |
| Fraud prevention          | &check;       |
| Reconciliation            | &cross;       |
| MO/TO                     | &cross;       |

</td><td valign="top">

| Payment Methods	  | Support       |
| ------------------- | ------------- |
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
