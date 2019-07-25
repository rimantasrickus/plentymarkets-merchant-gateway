<?php
namespace Heidelpay\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Models\InvoiceGuaranteedSetting;
use Heidelpay\Configuration\PluginConfiguration;

class CreateInvoiceGuaranteedSettingTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(InvoiceGuaranteedSetting::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME.'::translation.migration',
                'InvoiceGuaranteedSetting Table created'
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
