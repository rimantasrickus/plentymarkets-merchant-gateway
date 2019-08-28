<?php
set_time_limit(0);

use heidelpayPHP\Resources\EmbeddedResources\Address;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Constants\Salutations;
use heidelpayPHP\Resources\Metadata;

$heidelpay = new \heidelpayPHP\Heidelpay(SdkRestApi::getParam('privateKey'));

// Invoice address
$invoiceAddressPlenty = SdkRestApi::getParam('invoiceAddress');
$name = $invoiceAddressPlenty['name2'].' '.$invoiceAddressPlenty['name3'];
$invoiceAddress = new Address();
$street = $invoiceAddressPlenty['address1'].' '.$invoiceAddressPlenty['address2'];
if (!empty($invoiceAddressPlenty['address3'])) {
    $street .= ', '.$invoiceAddressPlenty['address3'];
}
$state = '';
if (!empty($invoiceAddressPlenty['stateName'])) {
    $state = $invoiceAddressPlenty['stateName'];
}
$invoiceAddress->setName($name)
    ->setStreet($street)
    ->setCity($invoiceAddressPlenty['town'])
    ->setZip($invoiceAddressPlenty['postalCode'])
    ->setState($state)
    ->setCountry($invoiceAddressPlenty['countryCode']);

// Delivery address
$deliveryAddressPlenty = SdkRestApi::getParam('deliveryAddress');
$deliveryAddress = new Address();
$name = $deliveryAddressPlenty['name2'].' '.$deliveryAddressPlenty['name3'];
$street = $deliveryAddressPlenty['address1'].' '.$deliveryAddressPlenty['address2'];
if (!empty($deliveryAddressPlenty['address3'])) {
    $street .= ', '.$deliveryAddressPlenty['address3'];
}
$state = '';
if (!empty($deliveryAddressPlenty['stateName'])) {
    $state = $deliveryAddressPlenty['stateName'];
}
$deliveryAddress->setName($name)
    ->setStreet($street)
    ->setCity($deliveryAddressPlenty['town'])
    ->setZip($deliveryAddressPlenty['postalCode'])
    ->setState($state)
    ->setCountry($deliveryAddressPlenty['countryCode']);

// Customer
$contactPlenty = SdkRestApi::getParam('contact');
$customer = CustomerFactory::createCustomer($contactPlenty['firstName'], $contactPlenty['lastName']);
$customer->setBirthDate($contactPlenty['birthday']);
if (!empty($contactPlenty['email'])) {
    $customer->setEmail($contactPlenty['email']);
}
if (!empty($contactPlenty['phone'])) {
    $customer->setPhone($contactPlenty['phone']);
}
if (!empty($contactPlenty['mobile'])) {
    $customer->setMobile($contactPlenty['mobile']);
}
$salutation = Salutations::UNKNOWN;
if ($contactPlenty['gender'] === 'male') {
    $salutation = Salutations::MR;
}
if ($contactPlenty['gender'] === 'female') {
    $salutation = Salutations::MRS;
}
$customer->setSalutation($salutation);
$customer->setBillingAddress($invoiceAddress);
$customer->setShippingAddress($deliveryAddress);
$paymentType = SdkRestApi::getParam('paymentType');

// Basket
$basketPlenty = SdkRestApi::getParam('basket');

//Metadata
$metadataPlenty = SdkRestApi::getParam('metadata');
$metadata = new Metadata();
$metadata->addMetadata('shopType', $metadataPlenty['shopType']);
$metadata->addMetadata('shopVersion', $metadataPlenty['shopVersion']);
$metadata->addMetadata('pluginVersion', $metadataPlenty['pluginVersion']);
$metadata->addMetadata('pluginType', $metadataPlenty['pluginType']);
try {
    $transaction = $heidelpay->charge(
        $basketPlenty['amountTotal'],
        $basketPlenty['currencyCode'],
        $paymentType['id'],
        SdkRestApi::getParam('checkoutUrl'),
        $customer,
        $orderId = SdkRestApi::getParam('orderId'),
        $metadata
    );

    // For invoice need to use isPending
    if (!$transaction->isError()) {
        return [
            'success' => true,
            'iban' => $transaction->getIban(),
            'bic' => $transaction->getBic(),
            'shortId' => $transaction->getShortId(),
            'descriptor' => $transaction->getDescriptor(),
            'holder' => $transaction->getHolder(),
            'amount' => $transaction->getAmount(),
            'paymentId' => $transaction->getPayment()->getId(),
            'chargeId' => $transaction->getId(),
            'currency' => $transaction->getPayment()->getCurrency(),
            'status' => $transaction->getPayment()->getStateName()
        ];
    }

    return [
        'merchantMessage' => $transaction->getMessage()->getCustomer(),
        'messageCode' => $transaction->getMessage()->getCode()
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
