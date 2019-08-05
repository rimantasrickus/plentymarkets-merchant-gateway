<?php

namespace HeidelpayMGW\Containers;

use Plenty\Plugin\Templates\Twig;

use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Configuration\PluginConfiguration;

/**
* Returns rendered InvoiceInformation twig template
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
* @package  heidelpayMGW/containers
*/
class InvoiceInformationContainer
{
    /**
     * Return rendered twig template with required data
     *
     * @param Twig $twig  Twig templating engine
     * @param SessionHelper $sessionHelper  Session helper to save information for latter use
     *
     * @return string
     */
    public function call(
        Twig $twig,
        SessionHelper $sessionHelper
    ): string {
        $invoiceInformation = $sessionHelper->getValue('paymentInformation')['transaction'];
        $data = [
            'paymentId' => $invoiceInformation['paymentId'] ?? '',
            'bic' => $invoiceInformation['bic'] ?? '',
            'iban' => $invoiceInformation['iban'] ?? '',
            'shortId' => $invoiceInformation['shortId'] ?? '',
            'holder' => $invoiceInformation['holder'] ?? ''
        ];

        return $twig->render(
            PluginConfiguration::PLUGIN_NAME.'::content.InvoiceInformation',
            $data
        );
    }
}
