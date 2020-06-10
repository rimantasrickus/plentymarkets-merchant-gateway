<?php

namespace HeidelpayMGW\Models;

use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * PaymentInformation model
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
 * @package  heidelpayMGW/models
 *
 * @author Rimantas <development@heidelpay.com>
 */
class PaymentInformation extends Model
{
    /** @var int $id  Autoincrement model ID in the database */
    public $id = 0;

    /** @var string $orderId  Plenty Order ID */
    public $orderId = '';

    /** @var string $externalOrderId  heidelpay Order ID */
    public $externalOrderId = '';

    /** @var string $paymentMethod  heidelpay payment method */
    public $paymentMethod = '';

    /** @var string $paymentType  heidelpay payment type ID */
    public $paymentType = '';

    /** @var array $transaction  heidelpay payment transaction information like payment ID, amount, redirect URL and so on */
    public $transaction = array();

    /**
     * Database table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        /** @noinspection OffsetOperationsInspection */
        return PluginConfiguration::PLUGIN_NAME.'::'. explode('\\', __CLASS__)[2];
    }

    /**
     * Set parameters of the model
     *
     * @param array $data  Parameters to set
     *
     * @return PaymentInformation
     */
    public function set($data): PaymentInformation
    {
        // if parameter is null we set default value
        $this->orderId = $data['orderId'] ?? '';
        $this->externalOrderId = $data['externalOrderId'] ?? '';
        $this->paymentMethod = $data['paymentMethod'] ?? '';
        $this->paymentType = $data['paymentType'] ?? '';
        $this->transaction = $data['transaction'] ?? array();
        
        return $this;
    }
}
