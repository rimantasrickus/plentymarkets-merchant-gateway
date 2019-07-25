<?php
namespace Heidelpay\Containers;

use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Log\Loggable;

use Heidelpay\Helpers\PaymentHelper;
use Heidelpay\Configuration\PluginConfiguration;
use Heidelpay\Repositories\PluginSettingRepository;
use Heidelpay\Repositories\InvoiceGuaranteedSettingRepository;
use Heidelpay\Repositories\InvoiceGuaranteedB2BSettingRepository;

class BuyNowButtonContainer
{
    use Loggable;

    public function call(
        Twig $twig,
        PaymentHelper $paymentHelper,
        PluginSettingRepository $pluginSettingRepo,
        InvoiceGuaranteedSettingRepository $invoiceGuaranteedRepo,
        InvoiceGuaranteedB2BSettingRepository $invoiceGuaranteedB2BRepo
    ) {
        $data = [
            'mopList' => $paymentHelper->getPaymentMethodList(),
            'publicKey' => $pluginSettingRepo->get()->publicKey,
            'routeName' => PluginConfiguration::PLUGIN_NAME,
            'invoice' => PluginConfiguration::PAYMENT_KEY_INVOICE,
            'invoiceGuaranteed' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED,
            'useInvoiceFactoring' => $invoiceGuaranteedRepo->get()->guaranteedOrFactoring,
            'invoiceGuaranteedB2B' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B,
            'useInvoiceB2BFactoring' => $invoiceGuaranteedB2BRepo->get()->guaranteedOrFactoring,
        ];

        return $twig->render(
            PluginConfiguration::PLUGIN_NAME.'::content.BuyNowButton',
            $data
        );
    }
}
