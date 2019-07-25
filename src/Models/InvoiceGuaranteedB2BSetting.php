<?php
namespace Heidelpay\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class InvoiceGuaranteedB2BSetting extends Model
{
    public $id = 1;
    public $isActive = false;
    public $displayName = '';
    public $basketMinTotal = '';
    public $basketMaxTotal = '';
    public $iconURL = '';
    public $guaranteedOrFactoring = false;
    public $reasonCodeCancel = '';
    public $reasonCodeReturn = '';
    public $reasonCodeCredit = '';

    public function getTableName(): string
    {
        return 'Heidelpay::InvoiceGuaranteedB2BSetting';
    }
    public function set($data)
    {
        $this->isActive = $data['isActive'] ?? false;
        $this->displayName = $data['displayName'] ?? '';
        $this->basketMinTotal = $data['basketMinTotal'] ?? '';
        $this->basketMaxTotal = $data['basketMaxTotal'] ?? '';
        $this->iconURL = $data['iconURL'] ?? '';
        $this->guaranteedOrFactoring = $data['guaranteedOrFactoring'] ?? false;
        $this->reasonCodeCancel = $data['reasonCodeCancel'] ?? '';
        $this->reasonCodeReturn = $data['reasonCodeReturn'] ?? '';
        $this->reasonCodeCredit = $data['reasonCodeCredit'] ?? '';
        
        return $this;
    }
}
