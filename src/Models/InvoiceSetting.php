<?php
namespace HeidelpayMGW\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class InvoiceSetting extends Model
{
    public $id = 1;
    public $isActive = false;
    public $displayName = '';
    public $basketMinTotal = '';
    public $basketMaxTotal = '';
    public $iconURL = '';

    public function getTableName(): string
    {
        return 'HeidelpayMGW::InvoiceSetting';
    }
    public function set($data)
    {
        $this->isActive = $data['isActive'] ?? false;
        $this->displayName = $data['displayName'] ?? '';
        $this->basketMinTotal = $data['basketMinTotal'] ?? '';
        $this->basketMaxTotal = $data['basketMaxTotal'] ?? '';
        $this->iconURL = $data['iconURL'] ?? '';
        
        return $this;
    }
}
