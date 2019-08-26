<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Services\ResourceService;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    $resourceService = new ResourceService($heidelpay);
    $event = json_decode(SdkRestApi::getParam('jsonRequest'), true);
    $resource = $resourceService->fetchResourceByUrl($event['retrieveUrl']);
    
    $data = array();
    if ($resource instanceof Payment) {
        $data = [
            'paymentId' => $resource->getId(),
            'paymentType' => $resource->getPaymentType()->getId(),
            'currency' => $resource->getCurrency(),
            'total' => $resource->getAmount()->getTotal(),
            'charged' => $resource->getAmount()->getCharged(),
            'canceled' => $resource->getAmount()->getCanceled(),
            'remaining' => $resource->getAmount()->getRemaining(),
            'stateName' => $resource->getStateName()
        ];
    }
    
    return $data;
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
