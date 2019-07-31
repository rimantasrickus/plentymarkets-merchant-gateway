<?php
namespace HeidelpayMGW\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use HeidelpayMGW\Models\InvoiceGuaranteedB2BSetting;

class InvoiceGuaranteedB2BSettingRepository
{
    private $database;

    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns InvoiceGuaranteedB2BSetting model.
     *
     * @return InvoiceGuaranteedB2BSetting
     */
    public function get($toArray = false)
    {
        $settings = $this->database->query(InvoiceGuaranteedB2BSetting::class)->get()[0] ?? pluginApp(InvoiceGuaranteedB2BSetting::class);
        
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
