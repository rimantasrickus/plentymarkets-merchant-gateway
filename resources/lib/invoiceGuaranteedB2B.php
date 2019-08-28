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
$customer = CustomerFactory::createNotRegisteredB2bCustomer(
    $contactPlenty['firstName'],
    $contactPlenty['lastName'],
    $contactPlenty['birthday'],
    $invoiceAddress,
    $contactPlenty['email'],
    $contactPlenty['company']
);
if (!empty($contactPlenty['phone'])) {
    $customer->setPhone($contactPlenty['phone']);
}
if (!empty($contactPlenty['mobile'])) {
    $customer->setMobile($contactPlenty['mobile']);
}
$customer->setShippingAddress($deliveryAddress);
$paymentType = SdkRestApi::getParam('paymentType');

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
$basketItem = new BasketItem();
$basketItem->setQuantity(1)
    ->setAmountGross($basketPlenty['amountTotalDiscount'])
    ->setType(BasketItemTypes::VOUCHER)
    ->setTitle($basketPlenty['discountTitle']);
$basket->addBasketItem($basketItem);

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
        'code' => $e->getCode()
    ];
} catch (Exception $e) {
    return [
        'merchantMessage' => $e->getMessage(),
        'code' => $e->getCode()
    ];
}
