<?php
namespace Heidelpay\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use Heidelpay\Models\PaymentInformation;

class PaymentInformationRepository
{
    private $database;
  
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns PaymentInformation model by plenty Order ID.
     *
     * @param int $orderId
     *
     * @return PaymentInformation|null
     */
    public function getByOrderId(int $orderId)
    {
        $model = $this->database->query(PaymentInformation::class)
            ->where('orderId', '=', $orderId)
            ->get()[0];

        return $model;
    }

    /**
     * Returns PaymentInformation model by external Order ID.
     *
     * @param string $externalOrderId
     *
     * @return PaymentInformation|null
     */
    public function getByExternalOrderId(string $externalOrderId)
    {
        $model = $this->database->query(PaymentInformation::class)
            ->where('externalOrderId', '=', $externalOrderId)
            ->get()[0];

        return $model;
    }

    /**
     * Returns PaymentInformation model by Heidelpay Payment ID.
     *
     * @param string $paymentType
     *
     * @return PaymentInformation|null
     */
    public function getByPaymentType(string $paymentType)
    {
        $model = $this->database->query(PaymentInformation::class)
            ->where('paymentType', '=', $paymentType)
            ->get()[0];

        return $model;
    }

    /**
     * Saves PaymentInformation model
     *
     * @param array $data
     *
     * @return PaymentInformation
     */
    public function save(array $data)
    {
        $model = pluginApp(PaymentInformation::class)->set($data);

        $this->database->save($model);
        
        return $model;
    }

    /**
     * Saves PaymentInformation model
     *
     * @param string $paymentType
     * @param array $data
     *
     * @return PaymentInformation
     */
    public function updateOrderId(string $paymentType, string $orderId)
    {
        $model = $this->getByPaymentType($paymentType);
        if (!empty($model)) {
            $model->orderId = $orderId;
            $this->database->save($model);
        }
        
        return $model;
    }

    /**
     * Saves PaymentInformation model
     *
     * @param string $paymentType
     * @param array $data
     *
     * @return PaymentInformation
     */
    public function update(string $paymentType, array $data)
    {
        $model = $this->getByPaymentType($paymentType);
        if (!empty($model)) {
            $model->set($data);
            $this->database->save($model);
        }
        
        return $model;
    }
}
