<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $cancelCharges = $payment->cancelAmount(SdkRestApi::getParam('amount'), SdkRestApi::getParam('reason'));

    return [
        'success' => true,
        'cancelCharges' => $cancelCharges,
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
