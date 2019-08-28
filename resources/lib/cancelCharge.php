<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $charges = $payment->getCharges();
    $amountLeft = SdkRestApi::getParam('amount');
    $cancelCharge = array();
    for ($i = count($charges)-1; $i >= 0; $i--) {
        if ($amountLeft >= $charges[$i]->getAmount()) {
            $cancel = $heidelpay->cancelCharge($charges[$i]->getId(), $charges[$i]->getAmount(), SdkRestApi::getParam('reason') ?? null);
            $cancelCharge[] = [
                'shortId' => $cancel->getShortId(),
                'amount' => $charges[$i]->getAmount(),
                'paymentId' => SdkRestApi::getParam('paymentId'),
                'reason' => SdkRestApi::getParam('reason')
            ];
        } else {
            $cancel = $heidelpay->cancelCharge($charges[$i]->getId(), $amountLeft, SdkRestApi::getParam('reason') ?? null);
            $cancelCharge[] = [
                'shortId' => $cancel->getShortId(),
                'amount' => $charges[$i]->getAmount(),
                'paymentId' => SdkRestApi::getParam('paymentId'),
                'reason' => SdkRestApi::getParam('reason')
            ];
        }
        $amountLeft -= $charges[$i]->getAmount();
        if ($amountLeft <= 0) {
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
