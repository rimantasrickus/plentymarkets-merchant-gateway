![Logo](https://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# heidelpay plentymarkets-merchant-gateway plugin
This extension provides an integration of the heidelpay payment methods for your plentymarkets shop.

Currently supported payment methods are:
* Invoice
* Invoice secured B2C
* Invoice secured B2B
* Cards (Credit and Debit)
* SEPA Direct Debit (guaranteed)
* PayPal
* SOFORT
* iDEAL
* FlexiPay Direct

## REQUIREMENTS
* This plugin is designed for Plentymarkets 7.

## Release notes
This module is based on the heidelpay php-sdk (https://github.com/heidelpay/heidelpayPHP).

## Installation
+ Please refer to [plentyKnowledge](https://knowledge.plentymarkets.com) in order to learn how to install plugins.
+ After performing the configuration steps described below you should be able to perform some tests in sandbox mode.

## Configuration
### Basic configuration
+ In the Plugin overview select the heidelpay plugin to switch to the configuration overview.
+ Switch to the `Default container links` and select all elements in the list and save the configuration.
+ For the plugin configuration please go to `System`->`Orders`->`Payment`->`Plugins`.

## Plugin settings
##### Public / Private key
Public key is required to create payment types by the browser.
Private key is required for transactions on heidelpay server.

The first letter of the keys determine the environment the plugin communicates with:
* `s` enables testing against the sandbox environment (no money is transferred).
* `p` enables production mode in which actual transactions take place and money is transferred.

> When you press the `Save` button the plugin registers heidelpay webhooks with the provided keys.
> These webhooks are used to synchronize the payments between Plentymarkets and heidelpay
> For example it updates the status of the payment in plentymarkets if the customer pays an invoice

##### Payment Method Parameters
##### Active
If checked the payment method will be selectable on the checkout page

##### Display Name
The name of the payment method shown on the checkout page. \
A default name will be shown if the input is empty.

##### Min-/Max-Total
The payment method will only be available if the basket has a total between these values.
Setting one of those values to 0 will disable the corresponding limitation.

##### URL to payment icon
This defines an icon for the payment method which is shown within the checkout in addition to the display name.
A default icon will be shown if the input is empty. \
Prerequisites for the url string:
* it must be reachable from the internet
* it must start with 'http://' or 'https://'
* it must end with '.jpg', '.png' or '.gif'

##### Card payment method
Card payments and paypal can be used in two different modes:
* Direct charge: the bank account of the customer will be charged directly.
* Authorize and charge: First the total amount will be reserved on the customer's account. Later you charge the money.

## Manual
For further information like a workflow description or how to create event procedures,
please refer to our [manual] (https://dev.heidelpay.com)