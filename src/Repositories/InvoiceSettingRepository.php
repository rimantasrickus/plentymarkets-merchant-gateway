<?php
namespace Heidelpay\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use Heidelpay\Models\InvoiceSetting;

class InvoiceSettingRepository
{
    private $database;

    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns InvoiceSetting model.
     *
     * @return InvoiceSetting
     */
    public function get($toArray = false)
    {
        $settings = $this->database->query(InvoiceSetting::class)->get()[0] ?? pluginApp(InvoiceSetting::class);
        
        return $toArray ? $this->toArray($settings) : $settings;
    }

    /**
     * Saves settings from UI
     *
     * @param array $data
     */
    public function save(array $data)
    {
        $model = $this->get()->set($data);

        $this->database->save($model);
        
        return $model;
    }

    private function toArray($obj)
    {
        return json_decode(json_encode($obj), true);
    }
}
