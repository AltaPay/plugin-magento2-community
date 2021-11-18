
# AltaPay Magento 2 Plugin

AltaPay, formerly AltaPay, supports major acquiring banks, global payment methods and over 50 preferred local schemes like Dankort in Denmark, Vipps and Bank Axept in Norway, Swish in Sweden etc., across multiple sales channels (in-store and terminals & eCommerce), geographies and currencies.

This includes credit and debit card acquiring, bank transfer networks, direct debit, wallets, mobile payment types, online invoicing, prepaid and gift card networks. With offices in Denmark, Iceland and UK, AltaPay serves Pan European and Global customers including JD Sports, Sports Direct, Paul Smith, Laura Ashley, DFDS Seaways, ZARA, ECCO and Stokke.


## Installing the Magento 2 Plugin

Installing this plugin will enable your website to handle card transactions through AltaPay's gateway.

We highly recommend gathering all the below information, before starting the installation.

### Prerequisites.
Before installing the plugin, you must install the "AltaPay module for Magento 2" on the merchant Magento 2 website.

Before configuring the plugin, you must have:

* AltaPay credentials:
    * Username
    * Password

* AltaPay gateway information:
    * Terminal
    * Gateway

These are provided by AltaPay.

Supported Versions
* Magento 2.0.7 - 2.4.x
* PHP 5.6.23 - 7.4.x

The package manager Composer (https://getcomposer.org/) must be installed on the server side.

Your private and public keys must be located at 'repo.magento.com' when installing the AltaPay module.

### Installation.

To install the module, you’ll have to use the command line.  Follow the steps below:

1. Navigate to the Magento 2 folder. <br>
   `cd <magento 2 folder>`
2. Download and install the module using Composer. <br>
   If asked for authentication, provide the username and password related to your Magento 2 account (see note below). <br>
   `$ composer requires AltaPay/magento2-payment`
3. Enable the AltaPay module. <br>
   `$ bin/magento module:enable SDM_altapay`
4. Check that the module is enabled. <br>
   `$ bin/magento module:status`
5. Run the Magento upgrade command <br>
   `$ bin/magento setup:upgrade`
6. Run the Magento compile command <br>
   `$ bin/magento setup:di:compile`
7. If necessary, correct the owner/group of the Magento 2 folders (see note below) <br>
   `$ sudo chown <owner>:<group> * -R`

### Notes.

If asked for authentication in step 2, use your Public Key as the username, and the Private Key as the password. This information can be found in the Secure Keys section of your Magento account:

![prerequisites](https://documentation.altapay.com/Content/Resources/Images/Plugins/Magento%202%20Plugin/Prerequisites.jpg)

`cd <magento 2 folder>`

(if need it) `$ sudo chown www-data:www-data * -R`



### Configuring the Magento 2 Plugin

You can configure the plugin to suit your (merchant's) needs. This includes adding payment methods and configuring payments.

First, connect the plugin to the AltaPay gateway.

1. Go to Stores > Configuration
2. Go to Sales > Payment Methods
3. Complete the ‘API Login’, ‘API Password’ and ‘Production URL’ fields with the gateway information for your environment (provided by AltaPay).
4. Click on the ‘Save Config’ button
5. If everything is correct, you should see the messages ‘Connection successful’ and ‘Authentication successful’ in the ‘Test connection’ and ‘Test authentication’ fields
   ![Configuring Magento](https://documentation.altapay.com/Content/Resources/Images/Plugins/Magento%202%20Plugin/Configuring%20the%20Magento%202.jpg)

To add the Terminals, take the following steps. You can add up to five terminals.

1. Enable the terminal
2. Choose a title for the terminal
3. Select the terminal name in the drop-down list
4. Optionally, you can also set the fields ‘Force language’, ‘Fraud detection’ and ‘Auto capture’ 5.    Save the changes by clicking on ‘Save Config’

![Configuring Magento](https://documentation.altapay.com/Content/Resources/Images/Plugins/Magento%202%20Plugin/Configuring%20the%20Magento%202_1.jpg)

### Updating the Magento 2 Plugin

The easiest way to update the module version is to first remove it, and then to follow the installation instructions in Installing the Magento 2 Plugin.

To remove the module, take the following steps:

1. `cd <magento 2 folder>`
2. `$ composer remove AltaPay/magento2-payment`



### FAQ on the Magento 2 Plugin

**PHP Warning: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.**

For orders that contain too many products, this PHP warning may be issued. You will need to:

* Open your php.ini file
* Edit the max_input_vars variable. This specifies the maximum number of variables that can be sent in a request. The default is 1000. Increase it to, say, 3000.
* Restart your server.

**Parameters: description/unitPrice/quantity are required for each orderline, but was not set for line: xxxx**

For orders that contain too many products, this PHP warning may be issued. You will need to:

* Open your php.ini file
* Edit the max_input_vars variable. This specifies the maximum number of variables that can be sent in a request. The default is 1000. Increase it to, say, 3000.
* Restart your server.
