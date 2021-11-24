# AltaPay for Magento2 Community

AltaPay has made it much easier for you as merchant/developer to receive secure payments in your Magento2 web shop.

[![Latest Stable Version](http://poser.pugx.org/altapay/magento2-community/v)](https://packagist.org/packages/altapay/magento2-community)
[![Total Downloads](http://poser.pugx.org/altapay/magento2-community/downloads)](https://packagist.org/packages/altapay/magento2-community)
[![License](http://poser.pugx.org/altapay/magento2-community/license)](https://packagist.org/packages/altapay/magento2-community)

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

### Prerequisites:

* Magento 2 with sample data should be installed on publically accessible URL
* Cypress should be installed
* For subscription test, "Push It Messenger Bag" product should be configured as subscription product

### Information: 

* These tests are for only Credit Card, Klarna DKK, and AltaPay subscription (Credit Card for subscription)
* In case, you don't want to test any of the above-mentioned payment methods, please leave it blank in the config file. i.e "CC_TERMINAL_NAME":""

### Steps:

* Install dependencies `npm i`
* Update "cypress/fixtures/config.json" 
* Execute `./node_modules/.bin/cypress run` in the terminal to run all the tests

## Changelog

See [Changelog](CHANGELOG.md) for all the release notes.

## License

Distributed under the MIT License. See [LICENSE](LICENSE) for more information.

## Documentation

For more details please see [AltaPay docs](https://github.com/AltaPay/plugin-magento2-community/wiki/Documentation)

## Contact
Feel free to contact our support team (support@altapay.com) if you need any assistance.
