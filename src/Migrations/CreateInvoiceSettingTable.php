<?php
namespace HeidelpayMGW\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Models\InvoiceSetting;
use HeidelpayMGW\Configuration\PluginConfiguration;

class CreateInvoiceSettingTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(InvoiceSetting::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME.'::translation.migration',
                'InvoiceSetting Table created'
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
