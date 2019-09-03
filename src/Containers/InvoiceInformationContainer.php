<?php

namespace HeidelpayMGW\Containers;

use Plenty\Plugin\Templates\Twig;

use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\PaymentInformationRepository;

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
     * @param PaymentInformationRepository $paymentInfoRepository  Payment information repository
     *
     * @return string
     */
    public function call(
        Twig $twig,
        PaymentInformationRepository $paymentInfoRepository,
        $args
    ): string {

        /** @var Order $order */
        $order = $args[0];
        if ($order instanceof Order) {
            $order = $order->toArray();
        }

        if (is_array($order)) {
            /** @var PaymentInformation $paymentInformation */
            $paymentInformation = $paymentInfoRepository->getByOrderId($order['id']);
            if (!empty($paymentInformation)
                && ($paymentInformation->paymentMethod === PluginConfiguration::INVOICE
                || $paymentInformation->paymentMethod === PluginConfiguration::INVOICE_GUARANTEED
                || $paymentInformation->paymentMethod === PluginConfiguration::INVOICE_FACTORING)
            ) {
                return $twig->render(
                    PluginConfiguration::PLUGIN_NAME.'::content.InvoiceInformation',
                    [
                        'paymentId' => $paymentInformation->transaction['paymentId'] ?? '',
                        'bic' => $paymentInformation->transaction['bic'] ?? '',
                        'iban' => $paymentInformation->transaction['iban'] ?? '',
                        'descriptor' => $paymentInformation->transaction['descriptor'] ?? '',
                        'holder' => $paymentInformation->transaction['holder'] ?? ''
                    ]
                );
            }
        }
        
        return '';
    }
}
