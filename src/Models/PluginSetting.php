<?php
namespace Heidelpay\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class PluginSetting extends Model
{
    public $id = 1;
    public $publicKey = '';
    public $privateKey = '';

    public function getTableName(): string
    {
        return 'Heidelpay::PluginSetting';
    }
    public function set($data)
    {
        $this->id = 1;
        $this->publicKey = $data['publicKey'] ?? '';
        $this->privateKey = $data['privateKey'] ?? '';
        
        return $this;
    }
}
