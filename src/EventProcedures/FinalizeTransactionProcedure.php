<?php

namespace HeidelpayMGW\EventProcedures;

use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Repositories\PaymentInformationRepository;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;

/**
 * Finalize transaction event
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
 * @package  heidelpayMGW/eventprocedures
 *
 * @author Rimantas <development@heidelpay.com>
 */
class FinalizeTransactionProcedure
{
    public function __construct()
    {
    }

    /**
     * Finalize transaction event
     *
     * @param EventProceduresTriggered $event
     * @param PaymentHelper $paymentHelper
     * @param PaymentInformationRepository $paymentInformationRepository
     *
     * @return void
     */
    public function handle(
        EventProceduresTriggered $event,
        PaymentHelper $paymentHelper,
        PaymentInformationRepository $paymentInformationRepository,
        OrderHelper $orderHelper
    ) {
        /** @var Order $order */
        $order = $event->getOrder();
        /** @var int $originalOrderId */
        $originalOrderId = $orderHelper->getOriginalOrderId($order);
        $paymentInformation = $paymentInformationRepository->getByOrderId($originalOrderId);
        if (empty($paymentInformation)) {
            return;
        }
        $paymentHelper->executeShipment($originalOrderId, $paymentInformation);
    }
}
