<?php

namespace HeidelpayMGW\Services;

use HeidelpayMGW\Helpers\Loggable;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;

/**
 * PlentyPaimentService class
 *
 * Copyright (C) 2019 heidelpay GmbH
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
 * @package  heidelpayMGW/services
 *
 * @author Rimantas <development@heidelpay.com>
 */
class PlentyPaymentService
{
    use Loggable;

    /** @var AuthHelper $authHelper  Plenty AuthHelper */
    private $authHelper;

    /**
     * BasketService constructor
     *
     * @param AuthHelper $authHelper  Plenty AuthHelper
     */
    public function __construct(
        AuthHelper $authHelper
    ) {
        $this->authHelper        = $authHelper;
    }
    
    /**
     * Get Plentymarkets payments by Plenty Order ID
     *
     * @param int $orderId  Plentymarkets Order ID
     *
     * @return Payment[]|null
     */
    public function getPlentyPayments(int $orderId)
    {
        /** @var PaymentRepositoryContract $paymentRepository */
        $paymentRepository = pluginApp(PaymentRepositoryContract::class);
        $payments = $this->authHelper->processUnguarded(
            function () use ($orderId, $paymentRepository) {
                return $paymentRepository->getPaymentsByOrderId($orderId);
            }
        );

        return $payments;
    }
}
