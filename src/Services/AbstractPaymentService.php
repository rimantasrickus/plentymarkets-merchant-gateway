<?php

namespace HeidelpayMGW\Services;

use Plenty\Plugin\Application;
use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\ApiKeysHelper;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Services\BasketService;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Payment\Models\Payment;
use HeidelpayMGW\Models\PaymentInformation;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Order\Models\OrderAmount;
use HeidelpayMGW\Services\PlentyPaymentService;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Account\Contact\Models\Contact;
use Plenty\Modules\Payment\Models\PaymentProperty;
use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Item\Variation\Models\Variation;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Payment\Models\PaymentOrderRelation;
use Plenty\Modules\Payment\Models\PaymentContactRelation;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Order\RelationReference\Models\OrderRelationReference;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreConfigurationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentContactRelationRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;

/**
 * AbstractPaymentService class
 *
 * Copyright (C) 2019 heidelpay GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link https://docs.heidelpay.com/
 *
 * @package  heidelpayMGW/services
 *
 * @author Rimantas <development@heidelpay.com>
 */
abstract class AbstractPaymentService
{
    use Loggable;

    const API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED = 'API.360.000.004';

    /** @var ContactRepositoryContract $contactRepository  Plenty Contact repository */
    private $contactRepository;

    /** @var WebstoreConfigurationRepositoryContract $webstoreConfigurationRepository  Plenty WebstoreConfiguration repository */
    private $webstoreConfigurationRepository;

    /** @var AuthHelper $authHelper  Plenty AuthHelper */
    private $authHelper;

    /** @var OrderHelper $orderHelper  Order manipulation with AuthHelper */
    private $orderHelper;

    /** @var BasketService $basketService  Service for checkout basket */
    private $basketService;

    /** @var SessionHelper $sessionHelper  Saves information for current plugin session */
    private $sessionHelper;
    
    /** @var ApiKeysHelper $apiKeysHelper  Returns Api keys depending if it's sandbox or production mode */
    protected $apiKeysHelper;

    /**
     * AbstractPaymentService constructor
     */
    public function __construct()
    {
        $this->contactRepository = pluginApp(ContactRepositoryContract::class);
        $this->webstoreConfigurationRepository = pluginApp(WebstoreConfigurationRepositoryContract::class);
        $this->authHelper = pluginApp(AuthHelper::class);
        $this->orderHelper = pluginApp(OrderHelper::class);
        $this->basketService = pluginApp(BasketService::class);
        $this->sessionHelper = pluginApp(SessionHelper::class);
        $this->apiKeysHelper = pluginApp(ApiKeysHelper::class);
    }

    /**
     * Make a charge call with Heidelpay PHP-SDK
     *
     * @param array $payment  Payment type information from Frontend JS
     *
     * @return array  Payment information from SDK
     */
    abstract public function charge(array $payment): array;

    /**
     * Make API call to cancel charge
     *
     * @param PaymentInformation $paymentInformation  Heidelpay payment information
     * @param Order $order  Plenty Order
     *
     * @return array  Response from SDK
     */
    abstract public function cancelCharge(PaymentInformation $paymentInformation, Order $order): array;

    /**
     * Prepare required data for Heidelpay charge call
     *
     * @param PaymentInformation $paymentInformation  Heidelpay payment information
     * @param Order $order  Plenty Order
     *
     * @return array  Data required for cancelCharge call
     */
    public function prepareCancelChargeRequest(PaymentInformation $paymentInformation, Order $order): array
    {
        /** @var float $returnAmount */
        $returnAmount = $order->amounts
            ->where('currency', '=', $paymentInformation->transaction['currency'])
            ->first()->invoiceTotal;
        /** @var float $paidAmount */
        $paidAmount = $order->parentOrder->amounts
            ->where('currency', '=', $paymentInformation->transaction['currency'])
            ->first()->paidAmount;

        $amount = $returnAmount > $paidAmount ? $paidAmount : $returnAmount;
        $data = [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'paymentId' => $paymentInformation->transaction['paymentId'],
            'chargeId' => $paymentInformation->transaction['chargeId'],
            'amount' => $amount
        ];

        return $data;
    }

    /**
     * Generate Heidelpay Order ID
     *
     * @param int $id  Plentymarkets checkout basket ID
     *
     * @return string  Generated Heidelpay Order ID
     */
    public function generateExternalOrderId(int $id): string
    {
        return uniqid($id . '.', true);
    }

    /**
     * Return array with contact information for Heidelpay customer object
     *
     * @param Address $address  Plenty Address model
     *
     * @return array  Data for Heidelpay customer object
     */
    public function contactInformation(Address $address): array
    {
        /** @var string $heidelpayBirthDate */
        $heidelpayBirthDate = $this->sessionHelper->getValue('heidelpayBirthDate');

        return [
            'firstName' => $address->firstName,
            'lastName' => $address->lastName,
            'email' => $address->email,
            'birthday' => $address->birthday === '' ? $heidelpayBirthDate : $address->birthday,
            'phone' => $address->phone,
            'mobile' => $address->personalNumber,
            'gender' => $address->gender
        ];
    }

    /**
     * Prepare information for Heidelpay charge call
     *
     * @param array $payment  Payment information from Frontend JS
     *
     * @return array  Data for charge call
     */
    public function prepareChargeRequest(array $payment)
    {
        /** @var Basket $basket */
        $basket = $this->basketService->getBasket();
        /** @var array $addresses */
        $addresses = $this->basketService->getCustomerAddressData();
        /** @var array $contact */
        $contact = $this->contactInformation($addresses['billing']);
        
        $addresses['billing']['countryCode'] = $this->basketService->getCountryCode((int)$addresses['billing']->countryId);
        $addresses['billing']['stateName'] = $this->basketService->getCountryState((int)$addresses['billing']->countryId, (int)$addresses['billing']->stateId);
        $addresses['shipping']['countryCode'] = $this->basketService->getCountryCode((int)$addresses['shipping']->countryId);
        $addresses['shipping']['stateName'] = $this->basketService->getCountryState((int)$addresses['shipping']->countryId, (int)$addresses['shipping']->stateId);
        /** @var string $externalOrderId */
        $externalOrderId = $this->generateExternalOrderId($basket->id);
        $this->sessionHelper->setValue('externalOrderId', $externalOrderId);

        return [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'checkoutUrl' => $this->getCheckoutUrl(),
            'basket' => $this->getBasketForAPI($basket),
            'invoiceAddress' => $addresses['billing'],
            'deliveryAddress' => $addresses['shipping'],
            'contact' => $contact,
            'orderId' => $externalOrderId,
            'paymentResource' => $payment,
            'metadata' => [
                'shopType' => 'Plentymarkets',
                'shopVersion' => '7',
                'pluginVersion' => PluginConfiguration::PLUGIN_VERSION,
                'pluginType' => 'plugin-HeidelpayMGW'
            ]
        ];
    }

    /**
     * Return array of basket data for Heidelpay Basket and BasketItem objects
     *
     * @param Basket $basket  Plenty Basket model
     *
     * @return array  Data for Heidelpay Basket and BasketItem objects
     */
    public function getBasketForAPI(Basket $basket)
    {
        /** @var VariationRepositoryContract $variationRepo */
        $variationRepo = pluginApp(VariationRepositoryContract::class);
        /** @var FrontendSessionStorageFactoryContract $sessionStorageFactory */
        $sessionStorageFactory = pluginApp(FrontendSessionStorageFactoryContract::class);
        $basketItems = array();
        $amountTotalVat = 0.0;
        /** @var BasketItem $basketItem */
        foreach ($basket->basketItems as $basketItem) {
            /** @var Variation $variation */
            $variation = $variationRepo->findById($basketItem->variationId);
            /** @var float $amountNet */
            $amountNet = $basketItem->price / (($basketItem->vat / 100) + 1);
            /** @var float $amountVat */
            $amountVat = $basketItem->price - $amountNet;
            /** @var float $amountTotalVat */
            $amountTotalVat += $amountVat;
            /** @var string $itemName */
            $itemName = $variation->name;
            if (empty($itemName)) {
                $itemName = $variation->itemTexts->where('lang', '=', $sessionStorageFactory->getLocaleSettings()->language)->first()->name;
            }
            $basketItems[] = [
                'basketItemReferenceId' => $basketItem->variationId,
                'quantity' => $basketItem->quantity,
                'vat' => $basketItem->vat,
                'amountGross' => round($basketItem->price, 2),
                'amountVat' => round($amountVat, 2),
                'amountPerUnit' => round(($basketItem->price/ $basketItem->quantity), 2),
                'amountNet' => round($amountNet, 2),
                'title' => $itemName ?: $basketItem->variationId
            ];
        }
        /** @var float $amountTotalDiscount */
        $amountTotalDiscount = round($basket->couponDiscount, 2) < 0 ? round($basket->couponDiscount, 2) * -1 : round($basket->couponDiscount, 2);
        
        return [
            'amountTotal' => round($basket->basketAmount, 2),
            'amountTotalDiscount' => $amountTotalDiscount,
            'amountTotalVat' => round($amountTotalVat, 2),
            'currencyCode' => $basket->currency,
            'shippingAmount' => round($basket->shippingAmount, 2),
            'shippingAmountNet' => round($basket->shippingAmountNet, 2),
            'shippingVat' => $basket->basketItems[0]->vat,
            'shippingTitle' => 'Shipping',
            'discountTitle' => 'Voucher',
            'basketItems' => $basketItems
        ];
    }

    /**
     * Update plentymarkets Order with external Order ID and comment
     *
     * @param int $orderId  Plenty Order ID
     * @param string $externalOrderId  Heidelpay Order ID
     *
     * @return void
     */
    public function addExternalOrderId(int $orderId, string $externalOrderId)
    {
        /** @var Order $order */
        $order = $this->orderHelper->findOrderById($orderId);
        /** @var OrderProperty $externalOrder */
        $externalOrder = pluginApp(OrderProperty::class);
        $externalOrder->typeId = OrderPropertyType::EXTERNAL_ORDER_ID;
        $externalOrder->value = $externalOrderId;
        $order->properties[] = $externalOrder;

        $this->orderHelper->updateOrder($order->toArray(), $orderId);
    }

    /**
     * Create payment and add to Order
     *
     * @param int $orderId  Plenty Order ID
     * @param string $referenceNumber  Heidelpay short ID
     * @param int $mopId  Method of payment ID
     *
     * @return Payment|null  Returns Plenty payment if success
     */
    public function addPaymentToOrder(
        int $orderId,
        string $referenceNumber,
        int $mopId,
        float $amount,
        string $currency
    ) {
        try {
            /** @var Order $order */
            $order = $this->orderHelper->findOrderById($orderId);
            /** @var Payment $payment */
            $payment = $this->createPlentyPayment($mopId, $referenceNumber, $order, $amount, $currency);
            if ($payment instanceof Payment) {
                $this->assignPaymentToOrder($payment, $order->id);

                return $payment;
            }
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }

        return null;
    }

    /**
     * Assign Payment to Order
     *
     * @param Payment $payment  Plenty Payment
     * @param int $orderId  Plenty Order ID
     *
     * @return PaymentOrderRelation  Plenty PaymentOrderRelation
     */
    public function assignPaymentToOrder(Payment $payment, int $orderId): PaymentOrderRelation
    {
        /** @var PaymentOrderRelationRepositoryContract $paymentOrderRelationRepo */
        $paymentOrderRelationRepo = pluginApp(PaymentOrderRelationRepositoryContract::class);
        /** @var Order $order */
        $order = $this->orderHelper->findOrderById($orderId);

        return $this->authHelper->processUnguarded(
            function () use ($paymentOrderRelationRepo, $payment, $order) {
                $paymentOrderRelationRepo->deleteOrderRelation($payment);
                
                return  $paymentOrderRelationRepo->createOrderRelation($payment, $order);
            }
        );
    }

    /**
     * Assign Payment to Contact
     *
     * @param Payment $payment  Plenty Payment
     * @param int $orderId  Plenty Order ID
     *
     * @return bool  Was relation created
     */
    public function assignPaymentToContact(Payment $payment, int $orderId): bool
    {
        /** @var Order $order */
        $order = $this->orderHelper->findOrderById($orderId);

        if (isset($order->relations)) {
            /** @var int $contactId */
            $contactId = $order->relations
                ->where('referenceType', OrderRelationReference::REFERENCE_TYPE_CONTACT)
                ->first()->referenceId;

            if (!empty($contactId)) {
                /** @var Contact $contact */
                $contact = $this->authHelper->processUnguarded(
                    function () use ($contactId) {
                        return  $this->contactRepository->findContactById($contactId);
                    }
                );
                if ($contact instanceof Contact) {
                    /** @var PaymentContactRelationRepositoryContract $paymentContactRelationRepo */
                    $paymentContactRelationRepo = pluginApp(PaymentContactRelationRepositoryContract::class);
                    /** @var PaymentContactRelation $paymentContactRelation */
                    $paymentContactRelation = $this->authHelper->processUnguarded(
                        function () use ($paymentContactRelationRepo, $payment, $contact) {
                            return  $paymentContactRelationRepo->createContactRelation($payment, $contact);
                        }
                    );
                    if ($paymentContactRelation instanceof PaymentContactRelation) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Create Plentymarkets payment
     *
     * @param int $mopId  Method of payment ID
     * @param string $referenceNumber  heidelpay short ID
     * @param Order $order  Plenty Order
     *
     * @return Payment|null  Returns Plenty payment if success
     */
    public function createPlentyPayment(int $mopId, string $referenceNumber, Order $order, float $amount, string $currency)
    {
        try {
            /** @var Payment $payment */
            $payment = pluginApp(Payment::class);
            $payment->mopId           = $mopId;
            $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
            $payment->status          = $this->getPaymentStatus($order, $amount, $currency);
            $payment->currency        = $currency;
            $payment->amount          = $amount;
            $payment->receivedAt      = date("Y-m-d G:i:s");
            $payment->hash            = $order->id.'-'.time();

            $paymentProperties = array();
            $paymentProperties[] = $this->getPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, 'Payment reference: '.$referenceNumber);
            $paymentProperties[] = $this->getPaymentProperty(PaymentProperty::TYPE_ORIGIN, (string)Payment::ORIGIN_PLUGIN);
            $payment->properties = $paymentProperties;

            /** @var PaymentRepositoryContract $paymentRepository */
            $paymentRepository = pluginApp(PaymentRepositoryContract::class);
            /** @var Payment $payment */
            $payment = $this->authHelper->processUnguarded(
                function () use ($paymentRepository, $payment) {
                    return  $paymentRepository->createPayment($payment);
                }
            );

            return $payment;
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }
        
        return null;
    }

    /**
     * Get payment status to assign to Plentymarkets payment
     *
     * @param Order $order  Plenty Order
     * @param float $amount  Payment amount
     * @param string $paymentCurrency  Payment currency
     *
     * @return int  Plenty Payment status
     */
    private function getPaymentStatus(Order $order, float $amount, string $paymentCurrency): int
    {
        /** @var OrderAmount $orderAmount */
        $orderAmount = $order->amounts->where('currency', '=', $paymentCurrency)->first();
        /** @var int $paymentStatus */
        $paymentStatus = Payment::STATUS_AWAITING_APPROVAL;
        if ($orderAmount->invoiceTotal === $amount && $amount !== 0.00) {
            $paymentStatus = Payment::STATUS_CAPTURED;
        }
        if ($orderAmount->invoiceTotal > $amount && $amount !== 0.00) {
            $paymentStatus = Payment::STATUS_PARTIALLY_CAPTURED;
        }

        return $paymentStatus;
    }

    /**
     * Make PaymentProperty object
     *
     * @param int $typeId  Plenty PaymentProperty type
     * @param string $value  Plenty PaymentProperty value
     *
     * @return PaymentProperty
     */
    private function getPaymentProperty(int $typeId, string $value): PaymentProperty
    {
        /** @var PaymentProperty $paymentProperty */
        $paymentProperty = pluginApp(PaymentProperty::class);
        $paymentProperty->typeId = $typeId;
        $paymentProperty->value = $value;

        return $paymentProperty;
    }

    /**
     * Add comment to Order
     *
     * @param int $orderId  Plenty Order
     * @param string $commentText  Comment text
     *
     * @return void
     */
    public function createOrderComment(int $orderId, string $commentText)
    {
        /** @var CommentRepositoryContract $commentRepository */
        $commentRepository = pluginApp(CommentRepositoryContract::class);
        $this->authHelper->processUnguarded(
            function () use ($orderId, $commentText, $commentRepository) {
                $commentRepository->createComment(
                    [
                        'referenceType'       => Comment::REFERENCE_TYPE_ORDER,
                        'referenceValue'      => $orderId,
                        'text'                => $commentText,
                        'isVisibleForContact' => true
                    ]
                );
            }
        );
    }

    /**
     * Get base url of Plentymarkets shop
     *
     * @return string  Plentymarkets shop base URL
     */
    public function getBaseUrl(): string
    {
        $webstore = $this->webstoreConfigurationRepository->findByPlentyId(pluginApp(Application::class)->getPlentyId());

        //https or http
        return ($webstore->domainSsl ?? $webstore->domain);
    }

    /**
     * Get base url of Plentymarkets shop
     *
     * @return string  Plentymarkets shop base URL
     */
    public function getCheckoutUrl(): string
    {
        return  $this->getBaseUrl().'/checkout';
    }

    /**
     * Update Payment amount
     *
     * @param int $orderId  Plenty Order ID
     * @param int $amount  Amount in cents
     * @param int $paymentStatus  Plenty Payment status
     *
     * @return bool  Was updated or not
     */
    public function updatePlentyPaymentPaidAmount(int $orderId, int $amount, int $paymentStatus): bool
    {
        try {
            /** @var PlentyPaymentService $plentyPaymentService */
            $plentyPaymentService = pluginApp(PlentyPaymentService::class);
            /** @var array $payments */
            $payments = $plentyPaymentService->getPlentyPayments($orderId);

            $this->updatePlentyPayment(
                (array)$payments,
                $orderId,
                $paymentStatus,
                false,
                $amount,
                true
            );
    
            return true;
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'log.exception',
                [
                    'message' => $e->getMessage()
                ]
            );
        }

        return false;
    }

    /**
     * Change payment status and add comment to Order
     *
     * @param string $externalOrderId  Heidelpay Order ID
     *
     * @return bool  Was payment status changed
     */
    abstract public function cancelPlentyPayment(string $externalOrderId): bool;

    /**
     * Change payment status to canceled
     *
     * @param Order $order  Plenty Order
     *
     * @return void
     */
    public function changePaymentStatusCanceled(Order $order)
    {
        $this->updatePlentyPayment(
            (array)$order->payments,
            $order->id,
            Payment::STATUS_CANCELED,
            true
        );
    }

    /**
     * Update Plentymarkets Order payments
     *
     * @param array $payments  Order payments
     * @param int $orderId  Order ID
     * @param int $paymentStatus  Payment status
     * @param bool $assignPaymentToOrder  Assign payment to Order
     * @param int $amount  Amount in cents
     * @param bool $updateOrderPaymentStatus  Update payment status
     *
     * @return void
     */
    private function updatePlentyPayment(
        array $payments,
        int $orderId,
        int $paymentStatus,
        bool $assignPaymentToOrder = false,
        int $amount = null,
        bool $updateOrderPaymentStatus = false
    ) {
        /** @var PaymentRepositoryContract $paymentRepository */
        $paymentRepository = pluginApp(PaymentRepositoryContract::class);
        /** @var PaymentHelper $paymentHelper */
        $paymentHelper = pluginApp(PaymentHelper::class);
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            if ($paymentHelper->isHeidelpayMGWMOP($payment->mopId)) {
                $payment->status = $paymentStatus;
                $payment->hash = $orderId.'-'.time();
                if (!empty($amount)) {
                    $payment->amount = $amount / 100;
                }
                if ($updateOrderPaymentStatus) {
                    $payment->updateOrderPaymentStatus = true;
                }

                $this->authHelper->processUnguarded(
                    function () use ($payment, $paymentRepository) {
                        return  $paymentRepository->updatePayment($payment);
                    }
                );
                if ($assignPaymentToOrder) {
                    $this->assignPaymentToOrder($payment, $orderId);
                }
                $this->assignPaymentToContact($payment, $orderId);
            }
        }
    }

    /**
     * Make API call ship to finalize transaction (if needed)
     *
     * @param PaymentInformation $paymentInformation  Heidelpay payment information
     * @param integer $orderId  Plenty Order ID
     *
     * @return array
     */
    public function ship(PaymentInformation $paymentInformation, int $orderId): array
    {
        /** @var Order $order */
        $order = $this->orderHelper->findOrderById($orderId);
        $invoiceId = '';
        foreach ($order->documents as $document) {
            if ($document->type ===  Document::INVOICE) {
                $invoiceId = $document->numberWithPrefix;
            }
        }
        /** @var LibraryCallContract $libCall */
        $libCall = pluginApp(LibraryCallContract::class);
        /** @var array $libResponse */
        $libResponse = $libCall->call(PluginConfiguration::PLUGIN_NAME.'::invoiceShip', [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'paymentId' => $paymentInformation->transaction['paymentId'],
            'invoiceId' => $invoiceId
        ]);

        $this->getLogger(__METHOD__)->debug(
            'translation.shipmentCall',
            [
                'orderId' => $orderId,
                'paymentId' => $paymentInformation->transaction['paymentId'],
                'invoiceId' => $invoiceId,
                'libResponse' => $libResponse
            ]
        );

        // since we know that most likely we get this error for invoice (unless something changes in the future) we just return
        if ($libResponse['code'] === self::API_ERROR_TRANSACTION_SHIP_NOT_ALLOWED) {
            return [
                'paymentInformation' => $paymentInformation,
                'orderId' => $orderId
            ];
        }

        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        /** @var string $commentText */
        $commentText = implode('<br />', [
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successShip')
        ]);

        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'translation.errorShip',
                [
                    'error' => $libResponse
                ]
            );

            $commentText = implode('<br />', [
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.errorShip'),
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage']
            ]);
        }

        $this->createOrderComment($orderId, $commentText);

        return $libResponse;
    }
}
