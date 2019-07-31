<?php
namespace HeidelpayMGW\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use HeidelpayMGW\Models\InvoiceGuaranteedSetting;

class InvoiceGuaranteedSettingRepository
{
    private $database;

    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns InvoiceGuaranteedSetting model.
     *
     * @return InvoiceGuaranteedSetting
     */
    public function get($toArray = false)
    {
        $settings = $this->database->query(InvoiceGuaranteedSetting::class)->get()[0] ?? pluginApp(InvoiceGuaranteedSetting::class);
        
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

    public function getReturnCode(string $returnId)
    {
        $model = $this->get();

        if ($model->reasonCodeCancel == $returnId) {
            return 'CANCEL';
        }
        if ($model->reasonCodeReturn == $returnId) {
            return 'RETURN';
        }
        if ($model->reasonCodeCredit == $returnId) {
            return 'CREDIT';
        }
    }

    private function toArray($obj)
    {
        return json_decode(json_encode($obj), true);
    }
}
