<?php
set_time_limit(0);

use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;

$heidelpay = new \heidelpayPHP\Heidelpay(SdkRestApi::getParam('privateKey'));

$contactPlenty = SdkRestApi::getParam('contact');
$b2bCustomer = SdkRestApi::getParam('b2bCustomer');

// Invoice address
$invoiceAddress = new Address();
$invoiceAddress->setStreet($b2bCustomer['street'])
    ->setCity($b2bCustomer['city'])
    ->setZip($b2bCustomer['zip'])
    ->setCountry($b2bCustomer['country']);
if ($b2bCustomer['companyRegistered']) {
    $invoiceAddress->setName($b2bCustomer['company']);
} else {
    $invoiceAddress->setName($b2bCustomer['firstName'].' '.$b2bCustomer['lastName']);
}

// Customer
if ($b2bCustomer['companyRegistered'] === 'registered') {
    $customer = CustomerFactory::createRegisteredB2bCustomer(
        $invoiceAddress,
        $b2bCustomer['commercialRegisterNumber'],
        $b2bCustomer['company'],
        $b2bCustomer['commercialSector']
    );
} else {
    $customer = CustomerFactory::createNotRegisteredB2bCustomer(
        $b2bCustomer['firstName'],
        $b2bCustomer['lastName'],
        $b2bCustomer['birthDate'],
        $invoiceAddress,
        $b2bCustomer['email'],
        $b2bCustomer['company'],
        $b2bCustomer['commercialSector']
    );
    $customer->setSalutation($b2bCustomer['salutation']);
}
if (!empty($contactPlenty['phone'])) {
    $customer->setPhone($contactPlenty['phone']);
}
if (!empty($contactPlenty['mobile'])) {
    $customer->setMobile($contactPlenty['mobile']);
}
$customer->setShippingAddress($invoiceAddress);

// Basket
$basketPlenty = SdkRestApi::getParam('basket');
$basket = new Basket();
$basket->setAmountTotalGross($basketPlenty['amountTotal'])
    ->setAmountTotalDiscount($basketPlenty['amountTotalDiscount'])
    ->setAmountTotalVat($basketPlenty['amountTotalVat'])
    ->setOrderId(SdkRestApi::getParam('orderId'))
    ->setCurrencyCode($basketPlenty['currencyCode']);

foreach ($basketPlenty['basketItems'] as $item) {
    $basketItem = new BasketItem();
    $basketItem->setBasketItemReferenceId($item['basketItemReferenceId'])
        ->setQuantity($item['quantity'])
        ->setVat($item['vat'])
        ->setAmountGross($item['amountGross'])
        ->setAmountVat($item['amountVat'])
        ->setAmountPerUnit($item['amountPerUnit'])
        ->setAmountNet($item['amountNet'])
        ->setType(BasketItemTypes::GOODS)
        ->setTitle($item['title']);
    $basket->addBasketItem($basketItem);
}
//shipping cost
$basketItem = new BasketItem();
$basketItem->setQuantity(1)
    ->setVat($basketPlenty['shippingVat'])
    ->setAmountGross($basketPlenty['shippingAmount'])
    ->setAmountVat($basketPlenty['shippingAmount'] - $basketPlenty['shippingAmountNet'])
    ->setAmountPerUnit($basketPlenty['shippingAmount'])
    ->setAmountNet($basketPlenty['shippingAmountNet'])
    ->setType(BasketItemTypes::SHIPMENT)
    ->setTitle($basketPlenty['shippingTitle']);
$basket->addBasketItem($basketItem);

//voucher
if ($basketPlenty['amountTotalDiscount'] > 0) {
    $basketItem = new BasketItem();
    $basketItem->setQuantity(1)
        ->setAmountGross($basketPlenty['amountTotalDiscount'])
        ->setType(BasketItemTypes::VOUCHER)
        ->setTitle($basketPlenty['discountTitle']);
    $basket->addBasketItem($basketItem);
}

//Metadata
$metadataPlenty = SdkRestApi::getParam('metadata');
$metadata = new Metadata();
$metadata->addMetadata('shopType', $metadataPlenty['shopType']);
$metadata->addMetadata('shopVersion', $metadataPlenty['shopVersion']);
$metadata->addMetadata('pluginVersion', $metadataPlenty['pluginVersion']);
$metadata->addMetadata('pluginType', $metadataPlenty['pluginType']);

$paymentResource = SdkRestApi::getParam('paymentResource');
try {
    $transaction = $heidelpay->charge(
        $basketPlenty['amountTotal'],
        $basketPlenty['currencyCode'],
        $paymentResource['id'],
        SdkRestApi::getParam('checkoutUrl'),
        $customer,
        $orderId = SdkRestApi::getParam('orderId'),
        $metadata,
        $basket
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
