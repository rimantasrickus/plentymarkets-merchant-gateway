<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;

$heidelpay = new \heidelpayPHP\Heidelpay(SdkRestApi::getParam('privateKey'));

try {
    $transaction = $heidelpay->ship(
        SdkRestApi::getParam('paymentId'),
        SdkRestApi::getParam('invoiceId')
    );

    if (!$transaction->isError()) {
        return [
            'success' => true,
        ];
    }

    return [
        'merchantMessage' => $transaction->getMessage()->getCustomer(),
    ];
} catch (HeidelpayApiException $e) {
    return [
        'merchantMessage' => $e->getMerchantMessage(),
        'clientMessage' => $e->getClientMessage(),
    ];
} catch (Exception $e) {
    return [
        'merchantMessage' => $e->getMessage(),
    ];
}
