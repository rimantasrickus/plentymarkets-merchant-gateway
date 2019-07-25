# plugin-Heidelpay

## Description
This plugin adds **Heidelpay** payment methods to Plentymarkets system. When user selects **Heidelpay**, plugin sends required information to API.

## Features

### Payment methods
Plugin adds payment methods to Plentymarkets system. All payment methods are registered with different IDs, so from Plentymarkets perspective they all are seperate. For plugin to work correctly You need to active `container links` in Ceres. You can do this by going to `Plugins`->`Plugin set where Heidelpay is activated`->`Heidelpay`->`Default container links`. Select all and press `Save`.

### Settings UI
Every payment method has it's own settings for min, max amounts, is particular payment method active or not and so on. Settings can be reached by going to `System`->`Orders`->`Payment`->`Plugins`->`Heidelpay`.

Prvate key: `s-priv-2a106rEYoeQiXIr4lNk46bj81mbpKQRH`\
Public key: `s-pub-2a10YKRQYEZ0IGMJFVUdCMntQO7Z7N7V`

## Code Entry Points
- [PluginSettingsController](./src/Controllers/PluginSettingsController.php): Comunicates with plugin's `Plugin settings` UI page
- [InvoiceSettingsController](./src/Controllers/InvoiceSettingsController.php): Comunicates with plugin's `Invoice settings` UI page
- [InvoiceGuaranteedSettingsController](./src/Controllers/InvoiceGuaranteedSettingsController.php): Comunicates with plugin's `Invoice guaranteed settings` UI page
- [InvoiceGuaranteedB2BSettingsController](./src/Controllers/InvoiceGuaranteedB2BSettingsController.php): Comunicates with plugin's `Invoice guaranteed settings` UI page
- [TestController](./src/Controllers/TestController.php): Plugin's DB management (migrate, update, show)
- [PaymetTypeController](./src/Controllers/PaymetTypeController.php): Listens for payment object from checkout
- [WebhooksController](./src/Controllers/WebhooksController.php): Listens for events from **Heidelpay**


## Technical Information

### Background Information
This plugin uses **Heidelpay** [JavaScript library](https://docs.heidelpay.com/docs/web-integration). In checkout is created payment object and then sent to backend and saved in plugin session using [FrontendSessionStorageFactoryContract](https://developers.plentymarkets.com/api-doc/Frontend#element_39). Plugin's [PluginServiceProvider](./src/Providers/PluginServiceProvider.php) listens for `GetPaymentMethodContent`, checks if we got **heidelpay** payment created by plugin, then gathers required information for API and does `charge` call. If there are no error, we save returned API information to DB. Comunication with API is done through [PHP-SDK](https://docs.heidelpay.com/docs/php-sdk). If everything is OK we go to next event `ExecutePayment`. If there are errors we stop further actions and display error in frontend, in checkout page. When plugin catches `ExecutePayment` Plentymarkets already created Order. We create Plentymarkets payment, assign it to this Order, add Order comment with additional payment information returned from API. For example `IBAN`, `BIC` and so on. The last event that plugin listens to is `OrderPdfGenerationEvent`. This event is called when Plentymarkets is about to generate PDF document. Dependant on document type we add additional information to `Invoice` PDF, send `cancelChargeById` or send `shipment` call.

### WebhooksController
We register webhook when in `Plugin settings` page we enter `Private key` and press `Save` button. **Heidelpay** then sends events to provided `URL`. Here we listen for different events. If we get `payment.canceled` event, we change particular payment's status to `STATUS_CANCELED` in Plentymarkets. If we get `payment.completed`, then we update payed amount and change Payment status to `STATUS_CAPTURED`.