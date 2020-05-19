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

### Return reasons
Invoice factoring payment method needs to have return reason when Order is canceled. To add return reason in Plentymarkets navigate to `System`->`Orders`->`Order types`->`Return`. Here added return reasons, You can select latter when creating return Order. 

## Plugin settings
##### Public key
Public key is required to create payment types by the browser

##### Private key
Public key is required for authentication on heidelpay server

##### Api mode
* Select parameter *'Sandbox'* to enable connection to the test environment, in which case any transactions will be transferred to the sandbox and will not be charged.  
Please make sure to use test credentials when this option is selected (ref. https://dev.heidelpay.com/sandbox-environment/).
* Select parameter *'Production'* to enable live mode which means that actual transactions will be executed and charged.
Please make sure to use your live credentials when this option is selected.

> When `Save` button is pressed plugin registers heidelpay webhooks with the provided keys.

##### Payment Method Parameters
##### Active
If checked the payment method will be selectable on the checkout page

##### Display Name
The name the payment method is shown under on the checkout page. \
A default name will be shown if left empty.

##### Min-/Max-Total
The payment method will only be available if the basket has a total between these values.
Setting one of those values to 0 will disable the corresponding limitation.

##### URL to payment icon
This defines an icon for the payment method which is shown within checkout in addition to the display name.
If left empty the default icon is used. \
Prerequisites for the url string:
* it must be reachable from the internet
* it must start with 'http://' or 'https://'
* it must end with '.jpg', '.png' or '.gif'

##### Card payment method
Card payments can be used in two different ways:
* Direct charge: the bank account of the customer is charged directly.
* Authorize and charge: First you reserve money on the customer's account and later you charge the money.

If customer has a card that uses 3D security, then during the checkout, customer will be redirected to a page where he can authorize his payment. If for some reason customer will not be able to authorize, he will be redirected back to the checkout. On the other hand if everything is OK, the Plentysystem will create an Order and customer will be redirected to Order status page.

## Workflow description
### Logging
To see Logs of Plentymarkets system navigate to `Data`->`Log` page. There You will see all the Logs of Plentymarkets system. Normally heidelpayMGW plugin will show only `error` level logs. Additionally You can enable `debug` level logs and this will show much more information of what is happening behind the scenes. To enable `debug` level logs, press `Configure logs` at the top-middle section of the `Log` page. In opened popup select `HeidelpayMGW` plugin, check `Active` checkbox, select duration for how long this configuration should be active and select `Debug` from `Log level` list.

### External Order number
When an Order is created with heidelpayMGW plugin, Order will have `External Order number` attached. This number is Order ID in your hIP (heidelpay Intelligence Platform). To find this number in Plentymarkets system navigate to `Edit orders` page. Search for the Order created with heidelpayMGW plugin and open it. In opened Order go to `Settings` tab and there You will see `Ext. Order number`.

### Status of a payment
To see what is the status of a payment for a given Order open that Order. Navigate to `Payment` tab. There You will see what status payment has right now. When payment changes in heidelpay system, plugin will receive event that payment changed and will change status of a payment in Plentymarkets system automatically.

### Creating Invoice
To create Invoice document for the Order, navigate to `Edit orders` page. Search for the Order You want to create Invoice for and click it. In opened Order go to `Receipts` tab. From the `Create receipt` list select `Invoice`. In the new window make changes if needed and press `Save` button. After that You will see created Invoice document. For the `Invoice` payment methods the additional payment information will be added to Invoice document automatically.
> If for some reason You are not able to create Invoice document, You need to check Your Invoice template. Go to `System`->`Client`->`{your shop}`->`Locations`->`Deutschland (standard)`->`Documents`->`Invoice` to do that.

### Creating delivery note
To create Delivery note document for the Order, navigate to `Edit orders` page. Search for the Order You want to create document for and click it. In opened Order go to `Receipts` tab. From the `Create receipt` list select `Delivery note`. In the new window make changes if needed and press `Save` button. After that You will see created document.
> If for some reason You are not able to create Delivery note document, You need to check Your document template. Go to `System`->`Client`->`{your shop}`->`Locations`->`Deutschland (standard)`->`Documents`->`Delivery note` to do that.

### Finalize invoice payment
> This section is relevant for `Invoice guaranteed` payment methods

In order to start the insurance of a payment you need to trigger a finalize transaction. To do this there are two possibilities:
* You can do this in your hIP account (heidelpay Intelligence Platform)
* You can do this by creating event procedure (Finalize transaction (HeidelpayMGW)) in shop backend (see [Creating event procedure](#creating-event-procedure))
> Recommendation is to create event procedure when Invoice is created
* The finalize starts the insurance period in which the customer has to pay the total amount of the order.
* The insurance period is determined within your contract with heidelpay.
* As soon as the total amount is paid by the customer a receipt transaction (REC) appears within the hIP and is sent to the pushUrl of your shop.
* The plugin will then update payments of the corresponding order.

### Cancel payment
To cancel payment You will need to create event procedure (Cancel transaction (HeidelpayMGW)) in shop backend (see [Creating event procedure](#creating-event-procedure)).
> Recommendation is to create event procedure when Credit node document is created
Usual workflow would be to navigate to original Order. In the `Overview` tab You will see list box named `Credit note...`. From list select `from individual items` or `from all positions`. In the opened popup select items You want to refund. Press save button. Plentymarkets will create new Credit note Order. Navigate to `Receipts` tab from the `Create receipt` list select `Credit note`. Adjust settings if needed and press `Save`. If event procedure is configured to trigger when Credit note is generated then plugin will send refund amount to heidelpay API.

> If Credit note Order will be partly cancellation of original sales Order, plugin will subtract shipping costs from Credit note Order total amount when sending data to HeidelpayMGW.

> If Credit note Order will be full cancellation of original sales Order, plugin will send total Credit note Order amount to HeidelpayMGW. If You would like to not include shipping costs in full cancellation then You can open Credit note Order, navigate to `Settings` tab and remove shipping costs by hand. If You want Credit note Orders to never have shipping costs included from original Order, You can navigate to `Setup`->`Orders`->`Settings` and change `Include shipping costs in credit note` value to `No`

### Creating event procedure
In order to add new event procedure You will need to take these steps:
* Go to `System`->`Orders`->`Events`
* At the bottom press button Add event procedure
* In dialog box name your configuration and select when this event should be fired, for example `Order change`->`Status change`
* Press Save
* When modal closes, tick `Active` checkbox and in `Procedures` section add Procedure that should handle fired event. In this case `Authorization charge (heidelpay)`
* After that save your configuration by pressing `Save` button at the top

### Direct charge
For this transaction no additional steps are needed. The bank account of the customer is charged directly and Plentymarkets Order will be updated with paid amount automatically.

### Authorize and charge
With this transaction type money will not be directly charged from the customer, so You will need to charge reserved amount latter. This can be done in two ways:
* You can do this by creating the delivery note within the shop backend (see [Creating delivery note](#creating-delivery-note))
* You can do this by using custom event procedure within Plentymarkets (see [Creating event procedure](#creating-event-procedure))

### Invoice payment methods
> _Invoice and Invoice secured B2C_ is only available under the following conditions:
> 1. The Country is either Germany or Austria
> 2. The address does not belong to a company

> _Invoice secured B2B_ is only available under the following conditions:
> 1. The Country is either Germany or Austria
> 2. The address does belong to a company

### All payment methods
* All payment methods will add `shortId` (the id of the transaction which lead to the payment i.e. Receipt, Debit or Capture) to the Order payment (see [Status of a payment](#status-of-a-payment)).
* In case of an error, error message will be added to the Order note and to the Plentymarkets Logs (see [Logging](#logging)).
