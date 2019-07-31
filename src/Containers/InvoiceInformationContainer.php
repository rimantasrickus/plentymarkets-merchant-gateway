<?php
namespace HeidelpayMGW\Containers;

use Plenty\Plugin\Templates\Twig;

use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Configuration\PluginConfiguration;

class InvoiceInformationContainer
{
    public function call(
        Twig $twig,
        SessionHelper $sessionHelper
    ) {
        $invoiceInformation = $sessionHelper->getValue('paymentInformation')['transaction'];
        $data = [
            'paymentId' => $invoiceInformation['paymentId'] ?? '',
            'bic' => $invoiceInformation['bic'] ?? '',
            'iban' => $invoiceInformation['iban'] ?? '',
            'descriptor' => $invoiceInformation['descriptor'] ?? '',
            'holder' => $invoiceInformation['holder'] ?? '',
        ];

        return $twig->render(
            PluginConfiguration::PLUGIN_NAME.'::content.InvoiceInformation',
            $data
        );
    }
}
