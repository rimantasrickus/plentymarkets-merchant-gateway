<?php
namespace HeidelpayMGW\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use HeidelpayMGW\Models\PluginSetting;

class PluginSettingRepository
{
    private $database;
  
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns PluginSetting model.
     *
     * @return PluginSetting
     */
    public function get($toArray = false)
    {
        $settings = $this->database->query(PluginSetting::class)->get()[0] ?? pluginApp(PluginSetting::class);
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

    /**
     * Convert object to array
     *
     * @param object $obj
     *
     * @return array
     */
    private function toArray($obj)
    {
        return json_decode(json_encode($obj), true);
    }
}
