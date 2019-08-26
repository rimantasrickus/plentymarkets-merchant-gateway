<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $charges = $payment->getCharges();
    $amountLeft = SdkRestApi::getParam('amount');
    for ($i = count($charges)-1; $i > 0; $i--) {
        if ($amountLeft >= $charges[$i]->getAmount()) {
            $heidelpay->cancelCharge($charge, $charges[$i]->getAmount(), SdkRestApi::getParam('reason') ?? null);
        } else {
            $heidelpay->cancelCharge($charge, $amountLeft, SdkRestApi::getParam('reason') ?? null);
        }
        $amountLeft -= $charges[$i]->getAmount();
        if ($amountLeft <= 0) {
            break;
        }
    }

    return [
        'success' => true
    ];
} catch (HeidelpayApiException $e) {
    return [
        'merchantMessage' => $e->getMerchantMessage(),
        'clientMessage' => $e->getClientMessage(),
        'code' => $e->getCode()
    ];
} catch (Exception $e) {
    return [
        'merchantMessage' => $e->getMessage(),
        'code' => $e->getCode()
    ];
}
