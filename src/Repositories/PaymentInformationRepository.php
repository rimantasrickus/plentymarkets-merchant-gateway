<?php

namespace HeidelpayMGW\Repositories;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

use HeidelpayMGW\Models\PaymentInformation;

/**
 * Payment information repository
 *
 * Copyright (C) 2020 heidelpay GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link https://docs.heidelpay.com/
 *
 * @package  heidelpayMGW/repositories
 *
 * @author Rimantas <development@heidelpay.com>
 */
class PaymentInformationRepository
{
    /** @var DataBase $database */
    private $database;
  
    /**
     * PaymentInformationRepository constructor
     *
     * @param DataBase $database
     */
    public function __construct(DataBase $database)
    {
        $this->database = $database;
    }

    /**
     * Returns PaymentInformation model by plenty Order ID
     *
     * @param int $orderId  Plenty Order ID
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
     * Returns PaymentInformation model by external Order ID
     *
     * @param string $externalOrderId  heidelpay Order ID
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
     * Returns PaymentInformation model by Payment resource ID.
     *
     * @param string $paymentResourceId  heidelpay payment type
     *
     * @return PaymentInformation|null  Returns PaymentInformation with information such as OrderId, ExternalOrderId,
     *                                  PaymentResourceId, payment transaction information, like shortId, amount and so on.
     */
    public function getByResourceId(string $paymentResourceId)
    {
        $model = $this->database->query(PaymentInformation::class)
            ->where('paymentType', '=', $paymentResourceId)
            ->get()[0];

        return $model;
    }

    /**
     * Saves PaymentInformation model
     *
     * @param array $data  Payment information array
     *
     * @return PaymentInformation
     */
    public function save(array $data): PaymentInformation
    {
        $model = pluginApp(PaymentInformation::class)->set($data);

        $this->database->save($model);
        
        return $model;
    }

    /**
     * Updates PaymentInformation model by adding Order ID
     *
     * @param string $paymentResourceId
     * @param array $data
     *
     * @return PaymentInformation|null
     */
    public function updateOrderId(string $paymentResourceId, string $orderId)
    {
        $model = $this->getByResourceId($paymentResourceId);
        if (!empty($model)) {
            $model->orderId = $orderId;
            $this->database->save($model);
        }
        
        return $model;
    }

    /**
     * Updates PaymentInformation model
     *
     * @param string $paymentResourceId
     * @param array $data
     *
     * @return PaymentInformation|null
     */
    public function update(string $paymentResourceId, array $data)
    {
        $model = $this->getByResourceId($paymentResourceId);
        if (!empty($model)) {
            $model->set($data);
            $this->database->save($model);
        }
        
        return $model;
    }
}
