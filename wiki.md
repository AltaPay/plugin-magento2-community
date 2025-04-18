# AltaPay Magento 2 Plugin

AltaPay, headquartered in Denmark, is an internationally focused fintech company within payments with the mission to make payments less complicated. We help our merchants grow and expand their business across payment channels by offering a fully integrated seamless omni-channel experience for online, mobile and instore payments, creating transparency and reducing the need for manual tasks with one centralized payment platform.

AltaPay’s platform automizes, simplifies, and protects the transaction flow for shop owners and global retail and e-commerce companies, supporting and integrating smoothly into the major ERP systems. AltaPay performs as a Payment Service Provider operating under The Payment Card Industry Data Security Standard (PCI DSS).

## Magento2 Payment plugin installation guide


Installing this plugin will enable your website to handle card transactions through AltaPay's gateway.

We highly recommend gathering all the below information, before starting the installation.


**Table of Contents**

[Prerequisites](#prerequisites)

[Installation](#installation)

[Configuration](#configuration)

[Charged currency setting](#charged-currency-setting)

[Configure order status](#configure-order-status)

[Payment page layout](#payment-page-layout)

* [Order summary settings](#order-summary-settings)

* [Checkout Page Logo](#checkout-page-logo)

[Checkout form styling](#checkout-form-styling)

[Configure fraud detection](#configure-fraud-detection)

[Configure terminals](#configure-terminals)

[Save credit cards](#save-credit-cards)

[Pay by link](#pay-by-link)

* [Pay by link email template](#pay-by-link-email-template)

[Auto-cancelling orders with pending payment](#auto-cancelling-orders-with-pending-payment)

[Reorder from the admin](#reorder-from-the-admin)

[Reconcile Orders](#reconcile-orders)

[Supported versions](#supported-versions)

[Styling](#styling)

[Plugin updates](#plugin-updates)

[Troubleshooting](#troubleshooting)

## Prerequisites

Before configuring the plugin, you need the below information. These can
be provided by AltaPay.

1.  AltaPay credentials:
    -   Username
    -   Password
2.  AltaPay gateway information:
    -   Terminal
    -   Gateway
3.  The package manager Composer (https://getcomposer.org/) must be
    installed on the server side.
4.  Your private and public keys must be located at **repo.magento.com**
    when installing the AltaPay module.

> **Note:** If the API user credentials have not yet been created, refer to the [Creating a New API User](#creating-a-new-api-user) section for step-by-step instructions.

## Installation

### Install from zip file

-   Search for "AltaPay Payment Gateway"

-   Download extension from Marketplace and place inside your project

     `root/app/code`

-   In Magento root directory run the following commands using the command line

     `php bin/magento setup:upgrade`

     `php bin/magento setup:static-content:deploy`

### Install via composer (Recommended)

-   In Magento root directory run the following commands using the
    command line

     `composer require altapay/magento2-community`  

     `php bin/magento setup:upgrade` 

     `php bin/magento setup:di:compile` 

     `php bin/magento setup:static-content:deploy`
    >
    > _Note: If asked for authentication, use your Public Key as the
    > username, and the Private Key as the password. This information can be
    > found in the Secure Keys section of your Magento account:_

    ![marketplace_account](docs/marketplace_account.png)

## Additional Steps

-   Check that the module is enabled  
    `php bin/magento module:status`

**SDM_Altapay** will appear in the module list, if not enabled run below
command

-   Enable the AltaPay module  
    `php bin/magento module:enable SDM_Altapay`

## Configuration

You can configure the plugin to suit your needs, including adding
payment methods and configuring payments.

1.  Connect the plugin to the AltaPay gateway

2.  Navigate to: Admin \> Stores \> Configuration \> Sales \> Payment Methods

3.  Complete the 'API Login', 'API Password' and 'Production URL' fields with the gateway information for your environment (provided by AltaPay)

    ![gateway_configuration](docs/gateway_configuration.jpg)

4.  Click: 'Save Config' button

> If everything is correct, you should see the messages 'Connection
> successful' and 'Authentication successful' in the 'Test connection'
> and 'Test authentication' fields.
>
> Once the API details are validated the terminals will be appeared in
> the terminal's dropdown in each terminal.

## Charged currency setting

You can select currency used for AltaPay payment processing from charged currency setting section.

* **Display currency:** The currency visible on the store view or display is used.

* **Global/Website currency:** The base currency is used.

    ![charged-currency-setting](docs/charged_currency_setting.png)

## Configure order status

Navigate to: Admin \> Stores \> Configuration \> Sales (Tab) \> Payment
Methods

![order_status_configuration](docs/order_status_configuration.png)

## Payment page layout

### Choose payment page layout

Choose one of the below options from `Payment page layout` dropdown to select the layout type for payment page.

![Payment page layout configuration](docs/checkout_form_style.jpg)

- `Default` This will use the styling from the checkout/theme.

    ![checkout_style](docs/checkout_style.jpg)

- `Checkout Independent` This will show the payment page independent from the theme styling. This will provide a visually appealing appearance seamlessly, without conflicting with the theme styling.

    ![custom_payment_page_layout](docs/custom_payment_page_layout.png)

### Order summary settings

Enabling this option will display prices for subtotal, shipping, and item price, including taxes, on the order summary grid.

### Checkout Page Logo

This logo will be displayed on the checkout page when the **Checkout Independent** option is selected. If left empty, the default store logo will be used.
(jpg, jpeg, gif, png)

![checkout_independent_logo](docs/checkout_independent_logo.png)


## Checkout form styling

Choose one of the below options from `Checkout form style` dropdown to change the styling of Credit Card form on the checkout page.

![Checkout form styling configuration](docs/checkout_form_style.jpg)

- `Legacy` Choose this option if legacy form is enabled from the gateway side.

    ![altapay_cc_legacy_form.png](docs/legacy_style.jpg)

- `Checkout` Select the option to show the Credit Card form in a modern look. Make sure the checkout form is enabled from the gateway side.

    ![Checkout Style](docs/checkout_style.jpg)

- `Custom` This option can be selected to implement custom styling on the payment page. Selecting this option will remove all the styling from the payment page.

## Configure fraud detection

Navigate to: Admin \> Stores \> Configuration \> Sales (Tab) \> Payment
Methods

![fraud_setting](docs/fraud_setting.png)


## Synchronize terminal

To synchronize the terminals with the gateway, click on the **Synchronize Terminals** button. This will fetch the latest terminals from the gateway and will automatically configure based on the store country.

![terminal_sync](docs/terminal_sync.jpg)

## Configure terminals

1.  Navigate to: Admin \> Stores \> Configuration \> Sales (Tab) \>
    Payment Methods

2.  Enable the terminal

3.  Choose a title for the terminal

4.  Select the terminal name in the drop-down list

5.  Optional fields: 'Custom Message', 'Force language', 'Secret',  'Is Apple Pay?', 'Apple Pay Form Label', 'Auto
    capture', 'Terminal Logo', 'Show both Logo and Title', 'Enable Customer Token Control', 'AVS', 'Enforce AVS', 'AVS accepted codes', 'Sort Order'.

6.  If Apple Pay terminal is selected from the Terminal dropdown, make sure to enable isapplepay option.

7.  Save changes by clicking 'Save Config'

    ![gateway_terminal_configuration](docs/gateway_terminal_configuration.jpg)

_**Note:** Remember to follow Magento's [Cache Management](https://docs.magento.com/user-guide/system/cache-management.html) guidelines to clear the site cache when updating the configurations._

## Save credit cards
1. To enable save credit cards option for customers follow the bellow steps
    * Navigate to Admin \> Stores \> Configuration \> Sales \> Payment Methods > AltaPay
    * Choose Terminal
    * "Enable Customer Token Control" to Yes
    * Choose Unscheduled Type from the list.

        ![save_card_config](docs/save_card_config.png)

2. A new field will be appeared on checkout page for saving the credit card for later use

    ![choose_saved_card](docs/choose_saved_card.png)

3. Customer can manage the `Saved Credit Cards` from customer dashboard

    ![saved_card_list](docs/saved_card_list.png)

## Pay by link
By performing the below steps a customer receives a payment link on the provided email.

1. Navigate to Admin \> Sales \> Order 

2. Click on the "Create new order" button

    ![create_an_order](docs/create_an_order.png)

3. Select an existing customer or create a new user

    ![add_customer](docs/add_customer.png)

4. Click on the "Add Product" button and choose any product from the list

    ![add_product](docs/add_product.png)

5. Then click on the "Add the selected product(s) to order" button

    ![product_list](docs/product_list.png)

6. Add Billing and Shipping Address 

    ![add_billing_address](docs/add_billing_address.png)

7. Choose a payment method

    ![select_payment_method](docs/select_payment_method.png)

8. Click on the "Submit Order" button

    ![submit_order](docs/submit_order.png)

### Pay by link email template
You can create a custom email template by following the link below and change the template from the dropdown: https://developer.adobe.com/commerce/frontend-core/guide/templates/email/

 ![change_email_template](docs/change_email_template.png)

## Auto-cancelling orders with pending payment

1. Navigate to: **Admin** > **Stores** > **Configuration** > **Sales (Tab)** > **Payment Methods**
2. Scroll to **AltaPay** section
3. Open **Auto-cancelling orders with pending payment** section and choose **Yes** to enable the configuration.
4. Choose **Exclude admin orders** option if you want to exclude order created from the admin panel.
5. Cancellation timeframe runs from the moment the order is generated.

    **Note** Specify the time duration in hours; for instance, 2 days equals 48 hours with a default field setting of 24 hours.

    ![auto-cancel-order-with-pending-payment](docs/auto_cancel_order_with_pending_payment.png)

## Reorder from the admin
To place an order through the admin, please follow the steps below:

- For re-ordering through the admin, follow the steps outlined in the link:
<https://experienceleague.adobe.com/docs/commerce-admin/stores-sales/shopper-tools/reorders-allow.html?lang=en/#reorder-from-the-admin>

- Ensure that you select AltaPay payment methods from the available list.

    ![reorder_from_admin](docs/reorder_from_admin.png)

- Once the order is created, an email containing the payment link will be sent to the customer.
- The admin can also locate the payment link under the **Payment Information** section.

## Reconcile Orders
In order to reconcile payments on Magento please follow the steps below:

1. Navigate to the Magento **Admin** page.
2. Select **Sales** > **Orders** in the left hand pane.
3. Select the order you want to view.
4. Copy the Reconciliation Identifier from the **Reconciliation Details** section.
 
   ![magento_order_view](docs/magento_order_view.png)
 
5. Navigate to AltaPay Gateway dashboard
6. Click on **FUNDING FILES** under **FINANCES** menu
7. Download the CSV file
8. Or you can find the payment in the transaction list, open the reconciliation file from there and download a csv file
9. Open the downloaded CSV file and match the **Reconciliation Identifier** with Magento's **Reconciliation Identifier**.

**Sample AltaPay Gateway CSV:**
    
![funding_list_csv](docs/funding_list_csv.png)

## Supported versions

| 7.4.0         | Magento 2.4 |
|---------------|-------------|
| **7.1.3+, 7.2.x** | **Magento 2.3** |

_For Magneto 2.2 and below version please install the extension from
here._  
<https://packagist.org/packages/altapay/magento2-payment>

## Creating a New API User

To create a new API user in your AltaPay account, please follow these steps:

- Log in to your AltaPay account.
- From the left menu, navigate to **Settings** > **API Keys**.

    ![api_key](docs/api_keys.png)
    
- Click on the **Create New API Key** button from top right corner.
- Fill in the required fields:
    - **Your current password**  
    - **Username**  
    - **Password**  
    - **Assign Shops**
    
    ![api_key](docs/create_api_key.png)
- After entering the details, click **Create**.

The new credentials can now be used as the API Login and API Password in the AltaPay API Login section.

## Styling

- You can change/update the **Payment Form Page** by navigating to the below path:

    <https://github.com/AltaPay/plugin-magento2-community/blob/main/view/frontend/templates/ordersummary.phtml>

- Use the below link to apply the **CSS** to the form page:

    <https://github.com/AltaPay/plugin-magento2-community/blob/main/view/frontend/web/css/ordersummary.css>

    **Note** If you've selected the `Custom Layout` option for your `Payment page layout` you can modify **Payment Form Page** and **CSS** from below paths respectively.

    **Payment Form Page Path** <https://github.com/AltaPay/plugin-magento2-community/blob/main/view/frontend/templates/external/ordersummary.phtml>

    **CSS Path** <https://github.com/AltaPay/plugin-magento2-community/blob/main/view/frontend/web/css/external/style.css>

- You can update the styling for the Checkout/Terminals page from below file

    <https://github.com/AltaPay/plugin-magento2-community/blob/add-custom-text-option-for-terminals/view/frontend/web/css/altapaycheckout.css>

>**Note:** It is recommended to extend the layout file instead of making changes in the plugin file directly. If you want to extend the layout file, use the below path in your custom module/theme:
>
><https://github.com/AltaPay/plugin-magento2-community/blob/main/view/frontend/layout/sdmaltapay_index_callbackform.xml>
>
>Please visit Magento's official documentation to know more about how to [Override a layout](https://devdocs.magento.com/guides/v2.4/frontend-dev-guide/layouts/layout-override.html).

## Plugin updates

From version 3.8.2 onwards, you will receive a notification in Magento 2 admin when a new version of the plugin is available for installation. We recommend to update the plugin regularly upon receiving such notifications to ensure optimal performance and access to the latest features.

![plugin_update](docs/plugin_update.png)

## Troubleshooting

* **Callbacks 403 error**

    - If you are getting 403 forbidden error in "Magento Commerce Cloud". It can be caused by **Fastly**, which blocks our callbacks. In this case, please contact **Fastly** support.
    - Make sure AltaPay's outgoing IPs are whitelisted. You can find the details for callback settings [here](https://documentation.altapay.com/Content/Ecom/Payment%20Pages/Payment%20Page%20Form%20Setup.htm).

* **PHP Warning: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.**

    For orders that contain too many products, this PHP warning may be issued. You will need to:

    - Open your php.ini file
    - Edit the max_input_vars variable. This specifies the maximum number of variables that can be sent in a request. The default is 1000. Increase it to, say, 3000.
    - Restart your server.

* **Parameters: description/unitPrice/quantity are required for each orderline, but was not set for line: xxxx**
    
    The same problem as above. The request is being truncated because the number of variables are exceeding the max_input_vars limit.


## Providing error logs to support team

You can find the logs from the below path:

**Debug logs:** ```<install_directory>/var/log/debug.log```
    
**Exception logs:** ```<install_directory>/var/log/exception.log```

**AltaPay plugin logs:** ```<install_directory>/var/log/altapay.log```

**Web server error logs**

**For Apache server** You can find it on **/var/log/apache2/error.log**

**For Nginx** it would be **/var/log/nginx/error.log**

**_Note: Your path may vary from the mentioned above._**
