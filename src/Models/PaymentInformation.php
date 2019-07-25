<?php
namespace Heidelpay\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class PaymentInformation extends Model
{
    public $id = 0;
    public $orderId = '';
    public $externalOrderId = '';
    public $paymentMethod = '';
    public $paymentType = '';
    public $transaction = array();

    public function getTableName(): string
    {
        return 'Heidelpay::PaymentInformation';
    }
    public function set($data)
    {
        $this->orderId = $data['orderId'] ?? '';
        $this->externalOrderId = $data['externalOrderId'] ?? '';
        $this->paymentMethod = $data['paymentMethod'] ?? '';
        $this->paymentType = $data['paymentType'] ?? '';
        $this->transaction = $data['transaction'] ?? array();
        
        return $this;
    }
}
