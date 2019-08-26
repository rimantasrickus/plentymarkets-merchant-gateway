<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Services\ResourceService;
use heidelpayPHP\Services\PaymentService;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    $paymentService = new PaymentService($heidelpay);
    $resourceService = new ResourceService($heidelpay);
    
    $charge = $resourceService->fetchChargeById(SdkRestApi::getParam('paymentId'), SdkRestApi::getParam('chargeId'));
    $paymentService->cancelCharge($charge, SdkRestApi::getParam('amount'), SdkRestApi::getParam('reason') ?? null);

    return [
        'success' => true,
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
