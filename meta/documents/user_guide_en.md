![Logo](https://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# heidelpay plentymarkets-merchant-gateway plugin
This extension provides an integration of the heidelpay payment methods for your plentymarkets shop.

Currently supported payment methods are:
* Invoice
* Invoice secured B2C
* Invoice secured B2B

## REQUIREMENTS
* This plugin is designed fo Plentymarkets 7.

## Release notes
This module is based on the heidelpay php-sdk (https://github.com/heidelpay/heidelpayPHP).

## Installation
+ Please refer to [plentyKnowledge](https://knowledge.plentymarkets.com) in order to learn how to install plugins.
+ After performing the configuration steps described below you should be able to perform some tests in staging mode.
+ If everything is fine you can change the configuration to live mode and deploy the plugin for the productive area to enable it for your clients.

## Configuration
### Basic configuration
+ Select the Plugin-tab and then "Plugin overview"
+ Select the heidelpay plugin to switch to the configuration overview.
+ Select `Default container links` tab. Select all elements in the list and save configuration.
+ For the plugin configuration please go to `System`->`Orders`->`Payment`->`Plugins`.

### The configuration
#### Plugin settings
###### Public key
Public key is required to create payment types by the browser

###### Private key
Public key is required for authentication on heidelpay server

###### Api mode
* Select parameter *'Sandbox'* to enable connection to the test environment, in which case any transactions will be transferred to the sandbox and will not be charged.  
Please make sure to use test credentials when this option is selected (ref. https://dev.heidelpay.com/sandbox-environment/).
* Select parameter *'Production'* to enable live mode which means that actual transactions will be executed and charged.
Please make sure to use your live credentials when this option is selected.


##### Payment Method Parameters
###### Active
If checked the payment method will be selectable on the checkout page

###### Display Name
The name the payment method is shown under on the checkout page. \
A default name will be shown if left empty.

###### Min-/Max-Total
The payment method will only be available if the basket has a total between these values.
Setting one of those values to 0 will disable the corresponding limitation.

###### URL to payment icon
This defines an icon for the payment method which is shown within checkout in addition to the display name.
If left empty the default icon is used. \
Prerequisites for the url string:
* it must be reachable from the internet
* it must start with 'http://' or 'https://'
* it must end with '.jpg', '.png' or '.gif'

###### Use Invoice Factoring instead of Invoice Guaranteed
When using `Invoice Guaranteed` You can change payment to use `Invoice Factoring` payment method instead.\
> In essence, Invoice factoring is the same as Invoice guaranteed with the only difference being the insurance company. Instead of an insurance company in the background a third party business takes care of the invoice, thus guaranteeing your payment.

##### Invoice Factoring payment cancel reasons
###### Reason for CANCEL
Map Plentymarkets item return reason from the list to heidelpay `CANCEL`

###### Reason for RETURN
Map Plentymarkets item return reason from the list to heidelpay `RETURN`

###### Reason for CREDIT
Map Plentymarkets item return reason from the list to heidelpay `CREDIT`

## Workflow description
### Invoice payment methods
* In order to start the insurance of a payment you need to trigger a finalize transaction (FIN)
  * You can do this in your hIP account (heidelpay Intelligence Platform)
  * or by creating the delivery note within the shop backend (e. g. by clicking `Create delivery note`).
* When triggering the finalize from the shop backend a note with the result will be added to the order.
* The finalize starts the insurance period in which the customer has to pay the total amount of the order.
* The insurance period is determined within your contract with heidelpay.
* As soon as the total amount is paid by the customer a receipt transaction (REC) appears within the hIP and is sent to the pushUrl of your shop.
The shop module will then update payment linked to the corresponding order.
* The bank information for the customer will be written on the invoice pdf automatically on creation.

> _Invoice and Invoice secured B2C_ is only available under the following conditions:
> 2. The Country is either Germany or Austria
> 3. The address does not belong to a company

> _Invoice secured B2B_ is only available under the following conditions:
> 2. The Country is either Germany or Austria
> 3. The address does belong to a company

### All payment methods
* Payments contain the txnId (which is the heidelpay orderId), the shortId (the id of the transaction which lead to the payment i.e. Receipt, Debit or Capture) and the origin (i.e. heidelpay).
* In case of an error, error message will be added to the Order note.
