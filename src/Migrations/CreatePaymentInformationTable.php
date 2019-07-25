<?php
namespace Heidelpay\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Models\PaymentInformation;
use Heidelpay\Configuration\PluginConfiguration;

class CreatePaymentInformationTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(PaymentInformation::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME.'::translation.migration',
                'PaymentInformation Table created'
            );
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}
