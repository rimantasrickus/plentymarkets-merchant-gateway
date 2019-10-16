<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;

$heidelpay = new \heidelpayPHP\Heidelpay(SdkRestApi::getParam('privateKey'));

try {
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));

    return [
        'success' => true,
        'paymentId' => $payment->getId(),
        'status' => $payment->getStateName(),
        'amount' => $payment->getAmount(),
        'currency' => $payment->getCurrency()
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
