<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;

$heidelpay = new \heidelpayPHP\Heidelpay(SdkRestApi::getParam('privateKey'));

try {
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $charges = array();
    foreach ($payment->getCharges() as $charge) {
        $charges[] = [
            'amount' => $charge->getAmount(),
            'id' => $charge->getId(),
            'isPending' => $resource->getCharge($charge->getId())->isPending()
        ];
    }
    $cancellations = array();
    foreach ($payment->getCancellations() as $key => $cancellation) {
        $cancellations[$key] = [
            'amount' => $cancellation->getAmount(),
            'id' => $cancellation->getId(),
        ];
        $parentResource = $cancellation->getParentResource();
        if ($parentResource instanceof Charge) {
            $cancellations[$key]['chargeId'] = $parentResource->getId();
            $cancellations[$key]['chargePending'] = $parentResource->isPending();
        }
        if ($parentResource instanceof Authorization) {
            $cancellations[$key]['authId'] = $parentResource->getId();
        }
    }

    return [
        'success' => true,
        'paymentId' => $payment->getId(),
        'paymentResourceId' => $payment->getPaymentType()->getId(),
        'currency' => $payment->getCurrency(),
        'status' => $payment->getStateName(),
        'total' => $payment->getAmount()->getTotal(),
        'charged' => $payment->getAmount()->getCharged(),
        'canceled' => $payment->getAmount()->getCanceled(),
        'remaining' => $payment->getAmount()->getRemaining(),
        'stateName' => $payment->getStateName(),
        'charges' => $charges,
        'cancellations' => $cancellations
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
