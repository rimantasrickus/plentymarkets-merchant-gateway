<?php
namespace Heidelpay\Containers;

use Plenty\Plugin\Templates\Twig;

use Heidelpay\Helpers\SessionHelper;
use Heidelpay\Configuration\PluginConfiguration;

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
