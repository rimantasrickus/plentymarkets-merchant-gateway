<?php

namespace HeidelpayMGW\EventProcedures;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\PaymentHelper;
use Plenty\Modules\Payment\Models\Payment;
use HeidelpayMGW\Models\PaymentInformation;
use HeidelpayMGW\Repositories\PaymentInformationRepository;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;

/**
 * Refund transaction event
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
 * @package  heidelpayMGW/eventprocedures
 *
 * @author Rimantas <development@heidelpay.com>
 */
class RefundTransactionProcedure
{
    use Loggable;

    private $paymentHelper;
    
    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Refund transaction event
     *
     * @param EventProceduresTriggered $event
     * @param PaymentHelper $paymentHelper
     * @param PaymentInformationRepository $paymentInformationRepository
     *
     * @return void
     */
    public function handle(
        EventProceduresTriggered $event,
        PaymentInformationRepository $paymentInformationRepository,
        OrderHelper $orderHelper
    ) {
        /** @var Order $order */
        $order = $event->getOrder();
        /** @var int $originalOrderId */
        $originalOrderId = $orderHelper->getOriginalOrderId($order);
        /** @var PaymentInformation $paymentInformation */
        $paymentInformation = $paymentInformationRepository->getByOrderId($originalOrderId);

        if (empty($paymentInformation)) {
            return;
        }
        
        $libResponse = $this->paymentHelper->cancelTransaction($paymentInformation, $order, $originalOrderId);
        if ($libResponse['success']) {
            $this->addPayments($libResponse['cancellations'], $order, $paymentInformation);
        }
    }

    private function addPayments(array $cancellations, Order $order, PaymentInformation $paymentInformation)
    {
        $paymentId = $paymentInformation->transaction['paymentId'];
        foreach ($cancellations as $heidelpayCancellation) {
            // add payment only for charged transactions
            if (empty($heidelpayCancellation['chargeId']) || !$heidelpayCancellation['chargeSuccess']) {
                continue;
            }
            $paymentHash = $this->paymentHelper->generatePaymentHash(
                $order->id,
                $paymentId,
                $heidelpayCancellation['chargeId'],
                $heidelpayCancellation['id']
            );
            // if order already has this charge, skip
            if ($this->paymentHelper->hasPayment($order, $paymentHash)) {
                continue;
            }
            $paymentReference = 'cancellation: '.$heidelpayCancellation['shortId'];
            $paymentReference .= ' charge: '.$heidelpayCancellation['chargeShortId'];
            $paymentReference .= ' paymentId: '.$paymentId;
            $this->paymentHelper->addPayment(
                $order->id,
                $order->methodOfPaymentId,
                $paymentInformation->transaction['currency'],
                $paymentReference,
                $heidelpayCancellation['amount'],
                $paymentHash,
                Payment::PAYMENT_TYPE_DEBIT
            );
        }
    }
}
