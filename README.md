# AltaPay for Magento2 Community

AltaPay has made it much easier for you as merchant/developer to receive secure payments in your Magento2.3.x web shop.

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
* For subscription test, "Push It Messenger Bag" product should be configured as Subscription product

### Information:

* Magento 2 with sample data should be installed on a publically accessible URL
* Cypress should be installed
* For subscription test, "Push It Messenger Bag" product should be configured as a subscription product

### Steps:

* Install dependencies `npm i`
* Update "cypress/fixtures/config.json" 
* Execute `./node_modules/.bin/cypress run` in the terminal to run all the tests

## Changelog

See [Changelog](CHANGELOG.md) for all the release notes.

## License

Distributed under the MIT License. See [LICENSE](LICENSE) for more information.