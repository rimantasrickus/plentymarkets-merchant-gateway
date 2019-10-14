<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $charges = array_reverse($payment->getCharges());
    $amountToCancel = SdkRestApi::getParam('amount');
    
    if (count($charges) === 0) {
        $authorize = $payment->getAuthorization();
        if ($authorize !== null) {
            $cancel = $authorize->cancel($amountToCancel);
        }
        return [
            'success' => true,
            'cancelCharges' => [
                'shortId' => $cancel->getShortId(),
                'amount' => $amountToCancel,
                'paymentId' => SdkRestApi::getParam('paymentId'),
                'reason' => SdkRestApi::getParam('reason')
            ]
        ];
    }

    $cancelCharge = array();
    /** @var Charge $charge */
    foreach ($charges as $charge) {
        if ($amountToCancel >= $charge->getAmount()) {
            try {
                $cancel = $charge->cancel(null, SdkRestApi::getParam('reason'));
                $cancelCharge[] = [
                    'shortId' => $cancel->getShortId(),
                    'amount' => $charge->getAmount(),
                    'paymentId' => SdkRestApi::getParam('paymentId'),
                    'reason' => SdkRestApi::getParam('reason')
                ];
            } catch (HeidelpayApiException $e) {
                continue;
            }
        } else {
            try {
                $cancel = $charge->cancel($amountToCancel, SdkRestApi::getParam('reason'));
                $cancelCharge[] = [
                    'shortId' => $cancel->getShortId(),
                    'amount' => $amountToCancel,
                    'paymentId' => SdkRestApi::getParam('paymentId'),
                    'reason' => SdkRestApi::getParam('reason')
                ];
            } catch (HeidelpayApiException $e) {
                continue;
            }
        }
        $amountToCancel -= $charge->getAmount();
        if ($amountToCancel <= 0) {
            break;
        }
    }

    return [
        'success' => true,
        'cancelCharges' => $cancelCharge,
    ];
} catch (HeidelpayApiException $e) {
    return [
        'merchantMessage' => $e->getMerchantMessage(),
        'clientMessage' => $e->getClientMessage(),
        'errorId' => $e->getErrorId(),
        'code' => $e->getCode()
    ];
} catch (Exception $e) {
    return [
        'merchantMessage' => $e->getMessage(),
        'code' => $e->getCode()
    ];
}
