<?php
namespace Heidelpay\Configuration;

class PluginConfiguration
{
    // Plugin
    const PLUGIN_NAME = "Heidelpay";
    const PLUGIN_VERSION = "0.1.0";
    const PLUGIN_KEY = "HeidelpayPaymentPlugin";
    
    const PAYMENT_KEY_INVOICE = "HEIDELPAYINVOICE";
    const INVOICE_FRONTEND_NAME = "HeidelpayInvoice";
    const PAYMENT_KEY_INVOICE_GUARANTEED = "HEIDELPAYINVOICEGUARANTEED";
    const INVOICE_GUARANTEED_FRONTEND_NAME = "HeidelpayInvoiceGuaranteed";
    const PAYMENT_KEY_INVOICE_GUARANTEED_B2B = "HEIDELPAYINVOICEGUARANTEEDB2B";
    const INVOICE_GUARANTEED_FRONTEND_NAME_B2B = "HeidelpayInvoiceGuaranteedB2B";

    // Plugin liscense
    const PLUGIN_SECRET = "pluginES";
    const LICENSE_URL = 'https://api.license.hashtages.com/license/';
    const LICENSE_TEXT = 'Default license text when you intall plugin.';

    const SLACK_API_TOKEN = "xoxp-5134885568-95223463526-551040067206-980cda07422853e33883e58d7f674409";
    const SLACK_CHANNEL_ID = "CG65PNTN1";
    const SLACK_API_URL = "https://slack.com/api/chat.postMessage";

    // Plugin statuses
    const NO_LICENSE = 'noLicense';
    const PAID = 'paid';
    const EXPIRED = 'expired';
    const TRIAL = 'trial';
    const FREE = 'free';
}
