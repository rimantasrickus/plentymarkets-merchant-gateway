<?php

namespace HeidelpayMGW\Configuration;

/**
* Constants for plugin
*
* Copyright (C) 2019 heidelpay GmbH
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @link  https://docs.heidelpay.com/
*
* @author  Rimantas  <development@heidelpay.com>
*
* @package  heidelpayMGW/configuration
*/
class PluginConfiguration
{
    // Plugin
    const PLUGIN_NAME = 'HeidelpayMGW';
    const PLUGIN_VERSION = '0.3.0';
    const PLUGIN_KEY = 'HeidelpayMGWPaymentPlugin';
    const API_SANDBOX = 'sandbox';
    const API_PRODUCTION = 'production';
    
    // Plugin payment methods
    const PAYMENT_KEY_INVOICE = "HeidelpayMGWINVOICE";
    const INVOICE_FRONTEND_NAME = "HeidelpayMGWInvoice";
    const PAYMENT_KEY_INVOICE_GUARANTEED = "HeidelpayMGWINVOICEGUARANTEED";
    const INVOICE_GUARANTEED_FRONTEND_NAME = "HeidelpayMGWInvoiceGuaranteed";
    const PAYMENT_KEY_INVOICE_GUARANTEED_B2B = "HeidelpayMGWINVOICEGUARANTEEDB2B";
    const INVOICE_GUARANTEED_FRONTEND_NAME_B2B = "HeidelpayMGWInvoiceGuaranteedB2B";
    const PAYMENT_KEY_CREDIT_CARD = "HeidelpayMGWCREDITCARD";
    const CREDIT_CARD_FRONTEND_NAME = "HeidelpayMGWCreditCard";
    const PAYMENT_KEY_SEPA = "HeidelpayMGWSEPA";
    const SEPA_FRONTEND_NAME = "HeidelpayMGWSEPA";
    const PAYMENT_KEY_SEPA_GUARANTEED = "HeidelpayMGWSEPAGUARANTEED";
    const SEPA_GUARANTEED_FRONTEND_NAME = "HeidelpayMGWSEPAGuaranteed";
    const PAYMENT_KEY_PAYPAL = "HeidelpayMGWPAYPAL";
    const PAYPAL_FRONTEND_NAME = "HeidelpayMGWPaypal";
    
    // Heidelpay payment methods
    const INVOICE_GUARANTEED = 'invoice-guaranteed';
    const INVOICE_FACTORING = 'invoice-factoring';
    const INVOICE = 'invoice';
    const CREDIT_CARD = 'card';
    const SEPA = 'sepa-direct-debit';
    const SEPA_GUARANTEED = 'sepa-direct-debit-guaranteed';

    // Payment mode
    const DIRECT_DEBIT = 'directDebit';
    const AUTHORIZATION_CAPTURE = 'authorizationCapture';
}
