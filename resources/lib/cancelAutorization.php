<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $authorization = $heidelpay->fetchAuthorization(SdkRestApi::getParam('paymentId'));
    if (SdkRestApi::getParam('amount')) {
        $cancel = $authorization->cancel(SdkRestApi::getParam('amount'));
    } else {
        $cancel = $authorization->cancel();
    }

    return [
        'success' => true,
        'shortId' => $cancel->getShortId(),
        'amount' => $cancel->getAmount(),
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
