<?php
namespace HeidelpayMGW\Configuration;

class PluginConfiguration
{
    // Plugin
    const PLUGIN_NAME = "HeidelpayMGW";
    const PLUGIN_VERSION = "0.1.0";
    const PLUGIN_KEY = "HeidelpayMGWPaymentPlugin";
    
    const PAYMENT_KEY_INVOICE = "HeidelpayMGWINVOICE";
    const INVOICE_FRONTEND_NAME = "HeidelpayMGWInvoice";
    const PAYMENT_KEY_INVOICE_GUARANTEED = "HeidelpayMGWINVOICEGUARANTEED";
    const INVOICE_GUARANTEED_FRONTEND_NAME = "HeidelpayMGWInvoiceGuaranteed";
    const PAYMENT_KEY_INVOICE_GUARANTEED_B2B = "HeidelpayMGWINVOICEGUARANTEEDB2B";
    const INVOICE_GUARANTEED_FRONTEND_NAME_B2B = "HeidelpayMGWInvoiceGuaranteedB2B";

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
