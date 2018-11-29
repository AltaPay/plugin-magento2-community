---version 1.0.1

## Getting Started

### Installation Instructions

#### Via Composer

Installing the extension is done via [composer](https://getcomposer.org/). 

##use the following command to install

composer require altapay/magento2-community

Once this is done, run the following commands:

<pre>
php bin/magento module:enable SDM_Altapay
php bin/magento setup:upgrade
php bin/magento setup:di:compile
</pre>


After installing, you can configure the module by:

1. Log in to your Magento back-end with the administrator account.
2. Go to `Stores` > `Configuration` > `Sales` > `Payment Methods`.
