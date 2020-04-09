<?php
set_time_limit(0);

use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    
    $payment = $heidelpay->fetchPayment(SdkRestApi::getParam('paymentId'));
    $cancelCharges = $payment->cancelAmount(SdkRestApi::getParam('amount'), SdkRestApi::getParam('reason'));
    $cancellations = array();
    foreach ($cancelCharges as $key => $cancellation) {
        // $cancellation = $cancellation->expose();
        $cancellations[$key] = [
            'amount' => $cancellation->getAmount(),
            'id' => $cancellation->getId(),
        ];
        $parentResource = $cancellation->getParentResource();
        if ($parentResource instanceof Charge) {
            $cancellations[$key]['chargeId'] = $parentResource->getId();
            $cancellations[$key]['chargePending'] = $payment->getCharge($parentResource->getId())->isPending();
        }
        if ($parentResource instanceof Authorization) {
            $cancellations[$key]['authId'] = $parentResource->getId();
        }
    }
    

    return [
        'success' => true,
        'cancellations' => $cancellations,
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
