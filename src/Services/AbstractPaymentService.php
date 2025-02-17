<?php

namespace HeidelpayMGW\Services;

use Plenty\Plugin\Application;
use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\ApiKeysHelper;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Payment\Models\Payment;
use HeidelpayMGW\Models\PaymentInformation;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Document\Models\Document;
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
 * Copyright (C) 2020 heidelpay GmbH
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
    
    /** @var ApiKeysHelper $apiKeysHelper  Returns the API keys */
    protected $apiKeysHelper;

    /** @var Translator $translator  Plenty Translator service */
    protected $translator;

    /** @var LibraryCallContract $libCall  Plenty LibraryCall */
    protected $libCall;

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
        $this->translator = pluginApp(Translator::class);
        $this->libCall = pluginApp(LibraryCallContract::class);
    }

    /**
     * Make a charge call with heidelpay PHP-SDK
     *
     * @param array $payment  Payment type information from Frontend JS
     *
     * @return array  Payment information from SDK
     */
    abstract public function charge(array $payment): array;

    /**
     * Make API call to cancel transaction
     *
     * @param PaymentInformation $paymentInformation
     * @param Order $order Plenty Order
     * @param int $originalOrderId Original Plenty Order ID
     *
     * @return array
     */
    public function cancelTransaction(PaymentInformation $paymentInformation, Order $order, int $originalOrderId): array
    {
        $data = $this->prepareCancelTransactionRequest($paymentInformation, $order);
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelTransaction', $data);
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAmount') . $data['amount']
        ]);
        
        if (!empty($libResponse['merchantMessage'])) {
            $commentText = implode('<br />', [
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.cancelTransactionError'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage']
            ]);

            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::translation.cancelTransactionError',
                [
                    'data' => $data,
                    'libResponse' => $libResponse
                ]
            );
        }

        $this->createOrderComment($originalOrderId, $commentText);

        $this->getLogger(__METHOD__)->debug(
            'translation.cancelTransaction',
            [
                'data' => $data,
                'libResponse' => $libResponse
            ]
        );
        
        return $libResponse;
    }

    /**
     * Prepare required data for heidelpay cancel call
     *
     * @param PaymentInformation $paymentInformation  heidelpay payment information
     * @param Order $order  Plenty Order
     *
     * @return array  Data required for cancelTransaction call
     */
    public function prepareCancelTransactionRequest(PaymentInformation $paymentInformation, Order $order): array
    {
        /** @var float $returnAmount */
        $returnAmount = $order->amounts
            ->where('currency', '=', $paymentInformation->transaction['currency'])
            ->first()->invoiceTotal;
        
        /** @var Order $originOrder */
        $originOrder = $this->orderHelper->getOriginalOrder($order);
        /** @var float $salesInvoiceTotal */
        $salesInvoiceTotal = $originOrder->amounts
            ->where('currency', '=', $paymentInformation->transaction['currency'])
            ->first()->invoiceTotal;

        // if partial return, don't include shipping costs
        if ($salesInvoiceTotal > $returnAmount) {
            $shippingCosts = $order->amounts
                ->where('currency', '=', $paymentInformation->transaction['currency'])
                ->first()->shippingCostsGross;
            $returnAmount = $returnAmount - $shippingCosts;
        }

        $data = [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'paymentId' => $paymentInformation->transaction['paymentId'],
            'amount' => $returnAmount
        ];

        return $data;
    }

    /**
     * Generate heidelpay Order ID
     *
     * @param int $id  Plentymarkets checkout basket ID
     *
     * @return string  Generated heidelpay Order ID
     */
    public function generateExternalOrderId(int $id): string
    {
        return uniqid($id . '.', true);
    }

    /**
     * Return array with contact information for heidelpay customer object
     *
     * @param Address $address  Plenty Address model
     *
     * @return array  Data for heidelpay customer object
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
     * Prepare information for heidelpay charge call
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
     * Return array of basket data for heidelpay Basket and BasketItem objects
     *
     * @param Basket $basket  Plenty Basket model
     *
     * @return array  Data for heidelpay Basket and BasketItem objects
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
        $amountTotalVat += $basket->shippingAmount - $basket->shippingAmountNet;

        $data = [
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

        $this->getLogger(__METHOD__)->debug(
            'translation.getBasketForAPI',
            [
                'basket' => $basket,
                'data' => $data
            ]
        );
        
        return $data;
    }

    /**
     * Update plentymarkets Order with external Order ID and comment
     *
     * @param int $orderId  Plenty Order ID
     * @param string $externalOrderId  heidelpay Order ID
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
     * @param string $referenceNumber  heidelpay payment ID and charge ID and cancellation ID
     * @param int $mopId  Plentymarkets method of payment ID
     * @param float $amount  Payment amount
     * @param string $currency  Payment currency
     * @param string $paymentHash Plentymarkets payment hash
     * @param string $paymentType  Plentymarkets payment type
     *
     * @return Payment|null  Returns Plenty payment if success
     */
    public function addPaymentToOrder(
        int $orderId,
        string $referenceNumber,
        int $mopId,
        float $amount,
        string $currency,
        string $paymentHash,
        string $paymentType
    ) {
        try {
            /** @var Order $order */
            $order = $this->orderHelper->findOrderById($orderId);
            /** @var Payment $payment */
            $payment = $this->createPlentyPayment($mopId, $referenceNumber, $amount, $currency, $paymentHash, $paymentType);
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
     * @param int $mopId  Plentymarkets method of payment ID
     * @param string $paymentReference  heidelpay payment ID and charge ID and cancellation ID
     * @param float $amount  Payment amount
     * @param string $currency  Payment currency
     * @param string $paymentHash Plentymarkets payment hash
     * @param string $paymentType  Plentymarkets payment type
     *
     * @return Payment|null  Returns Plenty payment if success
     */
    public function createPlentyPayment(
        int $mopId,
        string $paymentReference,
        float $amount,
        string $currency,
        string $paymentHash,
        string $paymentType
    ) {
        try {
            /** @var Payment $payment */
            $payment = pluginApp(Payment::class);
            $payment->mopId           = $mopId;
            $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
            $payment->status          = $paymentType === Payment::PAYMENT_TYPE_CREDIT ? Payment::STATUS_CAPTURED : Payment::STATUS_CANCELED;
            $payment->currency        = $currency;
            $payment->amount          = $amount;
            $payment->receivedAt      = date("Y-m-d G:i:s");
            $payment->hash            = $paymentHash;
            $payment->type            = $paymentType;

            $paymentProperties = array();
            $paymentProperties[] = $this->getPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, $paymentReference);
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
     * Make API call ship to finalize transaction (if needed)
     *
     * @param PaymentInformation $paymentInformation  heidelpay payment information
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

        /** @var array $libResponse */
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::invoiceShip', [
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

        /** @var string $commentText */
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successShip')
        ]);

        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::translation.errorShip',
                [
                    'error' => $libResponse
                ]
            );

            $commentText = implode('<br />', [
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.errorShip'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage']
            ]);
        }

        $this->createOrderComment($orderId, $commentText);

        return $libResponse;
    }
}
