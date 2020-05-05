<?php
set_time_limit(0);

use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));
    $resource = $heidelpay->fetchResourceFromEvent(SdkRestApi::getParam('jsonRequest'));
    
    $data = array();
    if ($resource instanceof Payment) {
        $charges = array();
        foreach ($resource->getCharges() as $charge) {
            $charge = $resource->getCharge($charge->getId());
            $charges[] = [
                'amount' => $charge->getAmount(),
                'id' => $charge->getId(),
                'isPending' => $charge->isPending(),
                'shortId' => $charge->getShortId(),
            ];
        }
        $resourceService = $heidelpay->getResourceService();
        $cancellations = array();
        /** @var Cancellation $cancellation */
        foreach ($resource->getCancellations() as $key => $cancellation) {
            $cancellation = $resourceService->fetchResource($cancellation);
            $cancellations[$key] = [
                'amount' => $cancellation->getAmount(),
                'id' => $cancellation->getId(),
                'shortId' => $cancellation->getShortId(),
            ];
            $parentResource = $cancellation->getParentResource();
            if ($parentResource instanceof Charge) {
                $cancellations[$key]['chargeId'] = $parentResource->getId();
                $cancellations[$key]['chargePending'] = $parentResource->isPending();
                $cancellations[$key]['chargeShortId'] = $parentResource->getShortId();
            }
            if ($parentResource instanceof Authorization) {
                $cancellations[$key]['authId'] = $parentResource->getId();
                $cancellations[$key]['authShortId'] = $parentResource->getShortId();
            }
        }
        $data = [
            'paymentId' => $resource->getId(),
            'paymentResourceId' => $resource->getPaymentType()->getId(),
            'currency' => $resource->getCurrency(),
            'total' => $resource->getAmount()->getTotal(),
            'charged' => $resource->getAmount()->getCharged(),
            'canceled' => $resource->getAmount()->getCanceled(),
            'remaining' => $resource->getAmount()->getRemaining(),
            'stateName' => $resource->getStateName(),
            'charges' => $charges,
            'cancellations' => $cancellations
        ];
    }
    
    return $data;
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
