<?php

namespace HeidelpayMGW\Helpers;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Payment\Models\Payment;
use HeidelpayMGW\Models\PaymentInformation;
use HeidelpayMGW\Services\SepaPaymentService;
use HeidelpayMGW\Services\IdealPaymentService;
use HeidelpayMGW\Services\PaypalPaymentService;
use HeidelpayMGW\Services\SofortPaymentService;
use HeidelpayMGW\Services\InvoicePaymentService;
use HeidelpayMGW\Services\FlexipayPaymentService;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Services\CreditCardPaymentService;
use HeidelpayMGW\Services\SepaGuaranteedPaymentService;
use Plenty\Modules\Order\Pdf\Models\OrderPdfGeneration;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use HeidelpayMGW\Services\InvoiceGuaranteedPaymentService;
use HeidelpayMGW\Repositories\PaymentInformationRepository;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use HeidelpayMGW\Services\InvoiceGuaranteedPaymentServiceB2B;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

/**
 * Helper class to handle payment data
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
 * @package  heidelpayMGW/helpers
 *
 * @author Rimantas <development@heidelpay.com>
 */
class PaymentHelper
{
    use Loggable;

    // payment events
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_CANCELED = 'canceled';
    const PAYMENT_PARTLY = 'partly';
    const PAYMENT_PENDING = 'pending';

    //Canceled order status
    const ORDER_CANCELED = 8.0;

    /** @var PaymentMethodRepositoryContract $plentyPaymentMethodRepository */
    private $plentyPaymentMethodRepository;

    /** @var SessionHelper $sessionHelper */
    private $sessionHelper;

    /** @var PaymentInformationRepository $heidelpayPaymentInformationRepo */
    private $heidelpayPaymentInformationRepo;
    
    public function __construct(
        PaymentMethodRepositoryContract $plentyPaymentMethodRepository,
        SessionHelper $sessionHelper,
        PaymentInformationRepository $heidelpayPaymentInformationRepo
    ) {
        $this->plentyPaymentMethodRepository = $plentyPaymentMethodRepository;
        $this->sessionHelper = $sessionHelper;
        $this->heidelpayPaymentInformationRepo = $heidelpayPaymentInformationRepo;
    }
 
    /**
     * Create the ID of the payment method if it doesn't exist yet
     *
     * @param string $payment  Plenty payment method's key to identify payment by
     *
     * @return void
     */
    public function createMopIfNotExists(string $payment)
    {
        // Check whether the ID of the plugin's payment method has been created
        if ($this->getPaymentMethod($payment) == -1) {
            //invoice
            if ($payment == PluginConfiguration::PAYMENT_KEY_INVOICE) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE,
                    'name' => PluginConfiguration::INVOICE_FRONTEND_NAME
                ];
     
                $this->plentyPaymentMethodRepository->createPaymentMethod($plentyPaymentMethodData);
            }
            //invoice guaranteed B2C
            if ($payment == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED,
                    'name' => PluginConfiguration::INVOICE_GUARANTEED_FRONTEND_NAME
                ];
     
                $this->plentyPaymentMethodRepository->createPaymentMethod($plentyPaymentMethodData);
            }
            //invoice guaranteed B2B
            if ($payment == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B,
                    'name' => PluginConfiguration::INVOICE_GUARANTEED_FRONTEND_NAME_B2B
                ];
            }
            //credit card
            if ($payment === PluginConfiguration::PAYMENT_KEY_CREDIT_CARD) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_CREDIT_CARD,
                    'name' => PluginConfiguration::CREDIT_CARD_FRONTEND_NAME
                ];
            }
            //Sepa
            if ($payment === PluginConfiguration::PAYMENT_KEY_SEPA) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_SEPA,
                    'name' => PluginConfiguration::SEPA_FRONTEND_NAME
                ];
            }
            //Sepa guaranteed
            if ($payment === PluginConfiguration::PAYMENT_KEY_SEPA_GUARANTEED) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_SEPA_GUARANTEED,
                    'name' => PluginConfiguration::SEPA_GUARANTEED_FRONTEND_NAME
                ];
            }
            //Paypal
            if ($payment === PluginConfiguration::PAYMENT_KEY_PAYPAL) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_PAYPAL,
                    'name' => PluginConfiguration::PAYPAL_FRONTEND_NAME
                ];
            }
            //iDEAL
            if ($payment === PluginConfiguration::PAYMENT_KEY_IDEAL) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_IDEAL,
                    'name' => PluginConfiguration::IDEAL_FRONTEND_NAME
                ];
            }
            //Sofort
            if ($payment === PluginConfiguration::PAYMENT_KEY_SOFORT) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_SOFORT,
                    'name' => PluginConfiguration::SOFORT_FRONTEND_NAME
                ];
            }
            //Flexipay
            if ($payment === PluginConfiguration::PAYMENT_KEY_FLEXIPAY) {
                $plentyPaymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_FLEXIPAY,
                    'name' => PluginConfiguration::FLEXIPAY_FRONTEND_NAME
                ];
            }
            if ($plentyPaymentMethodData !== null) {
                $this->plentyPaymentMethodRepository->createPaymentMethod($plentyPaymentMethodData);
            }
        }
    }
 
    /**
     * Return the ID for the payment method
     *
     * @param string $payment  Plenty payment method's key to identify payment by
     *
     * @return int -1 if did not find plugin's payment methods
     */
    public function getPaymentMethod(string $payment): int
    {
        /** @var array $plentyPaymentMethods */
        $plentyPaymentMethods = $this->plentyPaymentMethodRepository->allForPlugin(PluginConfiguration::PLUGIN_KEY);
 
        if (!empty($plentyPaymentMethods)) {
            /** @var PaymentMethod $plentyPaymentMethod */
            foreach ($plentyPaymentMethods as $plentyPaymentMethod) {
                if ($plentyPaymentMethod->paymentKey == $payment) {
                    return $plentyPaymentMethod->id;
                }
            }
        }
 
        return -1;
    }

    /**
     * Get plugin's payment method list
     *
     * @return array  Payment method list with id and payment key
     */
    public function getPaymentMethodList(): array
    {
        /** @var array $plentyPaymentMethods */
        $plentyPaymentMethods = $this->plentyPaymentMethodRepository->allForPlugin(PluginConfiguration::PLUGIN_KEY);
        
        $mopList = array();
        if (!empty($plentyPaymentMethods)) {
            /** @var PaymentMethod $plentyPaymentMethod */
            foreach ($plentyPaymentMethods as $plentyPaymentMethod) {
                $mopList[] = [
                    'id' => $plentyPaymentMethod->id,
                    'paymentKey' => $plentyPaymentMethod->paymentKey
                ];
            }
        }

        return $mopList;
    }

    /**
     * Check if mop ID is HeidelpayMGW
     *
     * @param int $mopId  Plenty payment method ID
     *
     * @return bool
     */
    public function isHeidelpayMGWMOP(int $mopId): bool
    {
        /** @var array $plentyMopList */
        $plentyMopList = $this->getPaymentMethodList();
        /** @var array $mop */
        foreach ($plentyMopList as $mop) {
            if ($mop['id'] === $mopId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select payment service, make charge call and handle result
     *
     * @param array $heidelpayPaymentResource  Heidelpay payment data from JS class in frontend
     * @param int $mopId  Plenty payment method ID
     *
     * @return array GetPaymentMethodContent event's value and type
     */
    public function executeCharge(array $heidelpayPaymentResource, int $mopId): array
    {
        // don't have orderId yet so we use 0
        /** @var mixed $pluginPaymentService */
        $pluginPaymentService = $this->getPluginPaymentService(0, $mopId);
        /** @var array $libResponse */
        $libResponse = $pluginPaymentService->charge($heidelpayPaymentResource);
        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::translation.exception',
                [
                    'error' => $libResponse
                ]
            );
            $value = 'Unexpected error';
            $type = GetPaymentMethodContent::RETURN_TYPE_ERROR;

            if (!empty($libResponse['clientMessage'])) {
                $value = $libResponse['clientMessage'];
            }

            return [
                'value' => $value,
                'type' => $type
            ];
        }

        unset($libResponse['success']);
        // save info for later
        $heidelpayPaymentInformation = [
            'orderId' => '',
            'externalOrderId' => $this->sessionHelper->getValue('externalOrderId'),
            'paymentType' => $heidelpayPaymentResource['id'],
            'paymentMethod' => $heidelpayPaymentResource['method'],
            'transaction' => $libResponse
        ];
        $this->heidelpayPaymentInformationRepo->save($heidelpayPaymentInformation);
        $this->sessionHelper->setValue('paymentInformation', $heidelpayPaymentInformation);

        if ($libResponse['redirectUrl']) {
            return [
                'value' => $libResponse['redirectUrl'],
                'type' => GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL
            ];
        }

        return [
            'value' => null,
            'type' => GetPaymentMethodContent::RETURN_TYPE_CONTINUE
        ];
    }

    /**
     * Select payment service based on method of payment
     *
     * @param int $orderId  Plenty Order ID
     * @param int $mopId  Plenty payment method ID
     *
     * @return mixed  Payment service class
     *
     * @throws \Throwable
     */
    public function getPluginPaymentService(int $orderId, int $mopId = null)
    {
        if (empty($mopId)) {
            /** @var Order $order */
            $order = pluginApp(OrderHelper::class)->findOrderById($orderId);
            $mopId = (int)$order->methodOfPaymentId;
        }
        /** @var array $pluginMopList */
        $pluginMopList = $this->getPaymentMethodList();
        /** @var array $mop */
        foreach ($pluginMopList as $mop) {
            if ($mop['id'] === $mopId) {
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_INVOICE) {
                    return pluginApp(InvoicePaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED) {
                    return pluginApp(InvoiceGuaranteedPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B) {
                    return pluginApp(InvoiceGuaranteedPaymentServiceB2B::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_CREDIT_CARD) {
                    return pluginApp(CreditCardPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_SEPA) {
                    return pluginApp(SepaPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_SEPA_GUARANTEED) {
                    return pluginApp(SepaGuaranteedPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_PAYPAL) {
                    return pluginApp(PaypalPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_IDEAL) {
                    return pluginApp(IdealPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_SOFORT) {
                    return pluginApp(SofortPaymentService::class);
                }
                if ($mop['paymentKey'] === PluginConfiguration::PAYMENT_KEY_FLEXIPAY) {
                    return pluginApp(FlexipayPaymentService::class);
                }
            }
        }

        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        throw new \Exception($translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentServiceException'));
    }

    /**
     * Handle Plenty payment creation from Heidelpay payment information
     *
     * @param array $heidelpayPayment  Payment transaction data from SDK
     * @param int $orderId  Plenty Order ID
     * @param int $mopId  Plenty payment method ID
     *
     * @return void
     */
    public function handlePayment(array $heidelpayPayment, int $orderId, int $mopId)
    {
        /** @var mixed $pluginPaymentService */
        $pluginPaymentService = $this->getPluginPaymentService($orderId);
        /** @var string $externalOrderId */
        $externalOrderId = $this->sessionHelper->getValue('externalOrderId');
        // handle invoice payment
        /** @var string $referenceNumber */
        $referenceNumber = $heidelpayPayment['transaction']['shortId'];
        // if payment completed add amount to payment
        $amount = 0.00;
        if ($heidelpayPayment['transaction']['status'] == 'completed') {
            $amount = (float)$heidelpayPayment['transaction']['amount'];
        }

        // add external Order ID and invoice information comment to Order
        $pluginPaymentService->addExternalOrderId($orderId, $externalOrderId);
        $plentyPayment = $pluginPaymentService->addPaymentToOrder(
            $orderId,
            $referenceNumber,
            $mopId,
            $amount,
            $heidelpayPayment['transaction']['currency']
        );
        $pluginPaymentService->assignPaymentToContact($plentyPayment, $orderId);
    }

    /**
     * Call plugin payment service's cancelTransaction method
     *
     * @param PaymentInformation $heidelpayPaymentInformation  Payment transaction data from SDK
     * @param Order $order  Plenty Order
     * @param int $originalOrderId  Plenty Order ID
     *
     * @return void
     */
    public function cancelTransaction(PaymentInformation $heidelpayPaymentInformation, Order $order, int $originalOrderId)
    {
        if (empty($heidelpayPaymentInformation->transaction)) {
            return;
        }

        /** @var mixed $pluginPaymentService */
        $pluginPaymentService = $this->getPluginPaymentService($originalOrderId);
        $pluginPaymentService->cancelTransaction($heidelpayPaymentInformation, $order, $originalOrderId);
    }

    /**
     * Handle Heidelpay webhook event
     *
     * @param array $paymentResource  Heidelpay payment information from SDK
     *
     * @return bool
     */
    public function handleWebhook(array $paymentResource): bool
    {
        if (empty($paymentResource['paymentResourceId'])) {
            return true;
        }
        if ($paymentResource['stateName'] === self::PAYMENT_PENDING) {
            return true;
        }
        /** @var PaymentInformation $heidelpayPaymentInfo */
        $heidelpayPaymentInfo = $this->heidelpayPaymentInformationRepo->getByResourceId($paymentResource['paymentResourceId']);
        if (empty($heidelpayPaymentInfo) || empty($heidelpayPaymentInfo->orderId)) {
            return false;
        }
        
        $updated = false;
        /** @var mixed $pluginPaymentService */
        $pluginPaymentService = $this->getPluginPaymentService((int)$heidelpayPaymentInfo->orderId);
        // payment completed logic
        if ($paymentResource['stateName'] === self::PAYMENT_COMPLETED) {
            /** @var Order $order */
            $order = pluginApp(OrderHelper::class)->findOrderById((int)$heidelpayPaymentInfo->orderId);
            // don't duplicate amount
            if ($order->paymentStatus !== 'fullyPaid') {
                $updated = $pluginPaymentService->updatePlentyPaymentPaidAmount((int)$heidelpayPaymentInfo->orderId, (int)($paymentResource['total'] * 100), Payment::STATUS_CAPTURED);
            } else {
                $updated = true;
            }
        }
        // payment partially completed logic
        if ($paymentResource['stateName'] === self::PAYMENT_PARTLY) {
            $updated = $pluginPaymentService->updatePlentyPaymentPaidAmount((int)$heidelpayPaymentInfo->orderId, (int)($paymentResource['charged'] * 100), Payment::STATUS_PARTIALLY_CAPTURED);
        }
        // payment canceled logic
        if ($paymentResource['stateName'] === self::PAYMENT_CANCELED) {
            $updated = $pluginPaymentService->cancelPlentyPayment($heidelpayPaymentInfo->externalOrderId);
            try {
                /** @var Order $order */
                $order = pluginApp(OrderHelper::class)->findOrderById((int)$heidelpayPaymentInfo->orderId);
                $order->statusId = self::ORDER_CANCELED;
                pluginApp(OrderHelper::class)->updateOrder($order->toArray(), (int)$heidelpayPaymentInfo->orderId);
            } catch (\Exception $e) {
                $this->getLogger(__METHOD__)->exception(
                    'translation.exception',
                    [
                        'error' => $e->getMessage()
                    ]
                );
                
                $updated = false;
            }
        }
        $this->getLogger(__METHOD__)->debug(
            'translation.paymentEvent',
            [
                'paymentResource' => $paymentResource,
                'heidelpayPaymentInfo' => $heidelpayPaymentInfo,
                'updated' => $updated
            ]
        );

        return $updated;
    }

    /**
     * Call payment service's ship method
     *
     * @param integer $orderId Plenty Order ID
     * @param PaymentInformation $paymentInformation  Payment transaction data from SDK
     *
     * @return void
     */
    public function executeShipment(int $orderId, PaymentInformation $paymentInformation)
    {
        /** @var mixed $pluginPaymentService */
        $pluginPaymentService = $this->getPluginPaymentService($orderId);
        /** @var array $libResponse */
        $libResponse = $pluginPaymentService->ship($paymentInformation, $orderId);
        
        $this->getLogger(__METHOD__)->debug(
            'translation.executeShipment',
            [
                'orderId' => $orderId,
                'paymentId' => $paymentInformation->transaction['paymentId'],
                'libResponse' => $libResponse
            ]
        );
    }

    /**
     * Return OrderPdfGeneration object for additional information in Invoice document
     *
     * @param PaymentInformation $paymentInformation  Payment transaction data from SDK
     * @param Order $order  Plenty Order
     *
     * @return OrderPdfGeneration|null  If we don't have transaction information to add return null
     */
    public function addInfoToInvoice(PaymentInformation $paymentInformation, Order $order)
    {
        // invoice payment's additional info
        if ($paymentInformation->paymentMethod === PluginConfiguration::INVOICE
            || $paymentInformation->paymentMethod === PluginConfiguration::INVOICE_GUARANTEED
            || $paymentInformation->paymentMethod === PluginConfiguration::INVOICE_FACTORING
        ) {
            if (empty($paymentInformation->transaction)) {
                return;
            }
            $language = 'DE';
            /** @var OrderProperty $property */
            foreach ($order->properties as $property) {
                if ($property->typeId === OrderPropertyType::DOCUMENT_LANGUAGE) {
                    $language = $property->value;
                }
            }
            /** @var Translator $translator */
            $translator = pluginApp(Translator::class);
            /** @var string $text */
            $text = implode(PHP_EOL, [
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.transferTo', [], $language),
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.iban', [], $language) . $paymentInformation->transaction['iban'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.bic', [], $language) . $paymentInformation->transaction['bic'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.holder', [], $language) . $paymentInformation->transaction['holder'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.descriptor', [], $language) . $paymentInformation->transaction['descriptor'],
            ]);
            /** @var OrderPdfGeneration $orderPdfGeneration */
            $orderPdfGeneration           = pluginApp(OrderPdfGeneration::class);
            // add payment information to the invoice pdf
            $orderPdfGeneration->language = $language;
            $orderPdfGeneration->advice   = $text;
            
            return $orderPdfGeneration;
        }
    }
}
