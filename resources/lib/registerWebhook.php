<?php
set_time_limit(0);

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Constants\WebhookEvents;
use heidelpayPHP\Heidelpay;

try {
    $heidelpay = new Heidelpay(SdkRestApi::getParam('privateKey'));

    foreach ($heidelpay->fetchAllWebhooks() as $webhook) {
        /** @var Webhook $webhook */
        if ($webhook->getUrl() === SdkRestApi::getParam('webhookUrl')) {
            $heidelpay->deleteWebhook($webhook);
        }
    }
    $webhook = $heidelpay->registerMultipleWebhooks(
        SdkRestApi::getParam('webhookUrl'),
        [
            WebhookEvents::CHARGE,
            WebhookEvents::CHARGEBACK,
            WebhookEvents::PAYMENT_PENDING,
            WebhookEvents::PAYMENT_COMPLETED,
            WebhookEvents::PAYMENT_CANCELED,
            WebhookEvents::PAYMENT_PARTLY,
            WebhookEvents::PAYMENT_PAYMENT_REVIEW,
            WebhookEvents::PAYMENT_CHARGEBACK
        ]
    );
    
    return [
        'success' => true
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
