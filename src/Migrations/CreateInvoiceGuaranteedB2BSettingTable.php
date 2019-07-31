<?php
namespace HeidelpayMGW\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Models\InvoiceGuaranteedB2BSetting;
use HeidelpayMGW\Configuration\PluginConfiguration;

class CreateInvoiceGuaranteedB2BSettingTable
{
    use Loggable;

    public function run(Migrate $migrate)
    {
        try {
            $migrate->createTable(InvoiceGuaranteedB2BSetting::class);

            $this->getLogger(__METHOD__)->info(
                PluginConfiguration::PLUGIN_NAME.'::translation.migration',
                'InvoiceGuaranteedB2BSetting Table created'
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
