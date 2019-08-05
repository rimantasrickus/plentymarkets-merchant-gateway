<?php

namespace HeidelpayMGW\Helpers;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Payment\Models\Payment;
use HeidelpayMGW\Models\PaymentInformation;
use HeidelpayMGW\Services\InvoicePaymentService;
use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Order\Pdf\Models\OrderPdfGeneration;
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
    const PAYMENT_COMPLETED = 'payment.completed';
    const PAYMENT_CANCELED = 'payment.canceled';
    const PAYMENT_PARTLY = 'payment.partly';
    const PAYMENT_PENDING = 'payment.pending';

    /** @var PaymentMethodRepositoryContract $paymentMethodRepository */
    private $paymentMethodRepository;

    /** @var SessionHelper $sessionHelper */
    private $sessionHelper;

    /** @var PaymentInformationRepository $paymentInformationRepo */
    private $paymentInformationRepo;
 
    public function __construct(
        PaymentMethodRepositoryContract $paymentMethodRepository,
        SessionHelper $sessionHelper,
        PaymentInformationRepository $paymentInformationRepo
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->sessionHelper = $sessionHelper;
        $this->paymentInformationRepo = $paymentInformationRepo;
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
                $paymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE,
                    'name' => PluginConfiguration::INVOICE_FRONTEND_NAME
                ];
     
                $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
            }
            //invoice guaranteed B2C
            if ($payment == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED) {
                $paymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED,
                    'name' => PluginConfiguration::INVOICE_GUARANTEED_FRONTEND_NAME
                ];
     
                $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
            }
            //invoice guaranteed B2B
            if ($payment == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B) {
                $paymentMethodData = [
                    'pluginKey' => PluginConfiguration::PLUGIN_KEY,
                    'paymentKey' => PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B,
                    'name' => PluginConfiguration::INVOICE_GUARANTEED_FRONTEND_NAME_B2B
                ];
     
                $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
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
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(PluginConfiguration::PLUGIN_KEY);
 
        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod->paymentKey == $payment) {
                    return $paymentMethod->id;
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
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(PluginConfiguration::PLUGIN_KEY);
        
        $mopList = array();
        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $mop) {
                $mopList[] = [
                    'id' => $mop->id,
                    'paymentKey' => $mop->paymentKey
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
    public function isHeidelpayMGWMOP($mopId): bool
    {
        $mopList = $this->getPaymentMethodList();
        foreach ($mopList as $mop) {
            if ($mop['id'] == $mopId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select payment service, make charge call and handle result
     *
     * @param array $paymentType  Heidelpay payment data from JS class in frontend
     * @param int $mopId  Plenty payment method ID
     *
     * @return array GetPaymentMethodContent event's value and type
     */
    public function executeCharge(array $paymentType, int $mopId): array
    {
        // don't have orderId yet so we use 0
        $paymentService = $this->getPaymentService(0, $mopId);
        $libResponse = $paymentService->charge($paymentType);
        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                'translation.exception',
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
        $paymentInformation = [
            'orderId' => '',
            'externalOrderId' => $this->sessionHelper->getValue('externalOrderId'),
            'paymentType' => $paymentType['id'],
            'paymentMethod' => $paymentType['method'],
            'transaction' => $libResponse
        ];
        $this->paymentInformationRepo->save($paymentInformation);
        $this->sessionHelper->setValue('paymentInformation', $paymentInformation);

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
     */
    private function getPaymentService(int $orderId, int $mopId = null)
    {
        if (empty($mopId)) {
            $order = pluginApp(OrderHelper::class)->findOrderById($orderId);
            $mopId = $order->methodOfPaymentId;
        }
        $pluginMopList = $this->getPaymentMethodList();

        foreach ($pluginMopList as $mop) {
            if ($mop['id'] == $mopId) {
                if ($mop['paymentKey'] == PluginConfiguration::PAYMENT_KEY_INVOICE) {
                    return pluginApp(InvoicePaymentService::class);
                }
                if ($mop['paymentKey'] == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED) {
                    return pluginApp(InvoiceGuaranteedPaymentService::class);
                }
                if ($mop['paymentKey'] == PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B) {
                    return pluginApp(InvoiceGuaranteedPaymentServiceB2B::class);
                }
            }
        }
    }

    /**
     * Handle Plenty payment creation from Heidelpay payment information
     *
     * @param array $payment  Payment transaction data from SDK
     * @param int $orderId  Plenty Order ID
     * @param int $mopId  Plenty payment method ID
     *
     * @return void
     */
    public function handlePayment(array $payment, int $orderId, int $mopId)
    {
        $paymentService = $this->getPaymentService($orderId);
        $externalOrderId = $this->sessionHelper->getValue('externalOrderId');
        // handle invoice payment
        $referenceNumber = $payment['transaction']['shortId'];
        // if payment completed add amount to payment
        $amount = 0.00;
        if ($payment['transaction']['status'] == 'completed') {
            $amount = (float)$payment['transaction']['amount'];
        }

        // add external Order ID and invoice information comment to Order
        $paymentService->updateOrder($orderId, $externalOrderId);
        $payment = $paymentService->addPaymentToOrder($orderId, $referenceNumber, $mopId, $amount, $payment['transaction']['currency']);
        $paymentService->assignPaymentToContact($payment, $orderId);
    }

    /**
     * Call payment service's cancelCharge method
     *
     * @param PaymentInformation $paymentInformation  Payment transaction data from SDK
     * @param Order $order  Plenty Order
     *
     * @return void
     */
    public function cancelCharge(PaymentInformation $paymentInformation, Order $order)
    {
        if (empty($paymentInformation->transaction)) {
            return;
        }
        $paymentService = $this->getPaymentService($order->parentOrder->id);
        $paymentService->cancelCharge($paymentInformation, $order);
    }

    /**
     * Handle Heidelpay webhook event
     *
     * @param array $hook  Heidelpay webhook information array
     * @param array $libResponse  Heidelpay payment information from SDK
     *
     * @return bool
     */
    public function handleWebhook(array $hook, array $libResponse): bool
    {
        if ($hook['event'] == self::PAYMENT_PENDING) {
            return true;
        }
        if (empty($libResponse['paymentType'])) {
            return false;
        }
        $paymentInfo = $this->paymentInformationRepo->getByPaymentType($libResponse['paymentType']);
        if (empty($paymentInfo) || empty($paymentInfo->orderId)) {
            return false;
        }
        
        $paymentService = $this->getPaymentService((int)$paymentInfo->orderId);
        // payment completed logic
        if ($hook['event'] == self::PAYMENT_COMPLETED) {
            $order = pluginApp(OrderHelper::class)->findOrderById((int)$paymentInfo->orderId);
            // don't duplicate amount
            if ($order->paymentStatus != 'fullyPaid') {
                $updated = $paymentService->updatePayedAmount((int)$paymentInfo->orderId, (int)($libResponse['total'] * 100), Payment::STATUS_CAPTURED);
            }
        }
        // payment partially completed logic
        if ($hook['event'] == self::PAYMENT_PARTLY) {
            $updated = $paymentService->updatePayedAmount((int)$paymentInfo->orderId, (int)($libResponse['total'] * 100), Payment::STATUS_PARTIALLY_CAPTURED);
        }
        // payment canceled logic
        if ($hook['event'] == self::PAYMENT_CANCELED) {
            $updated = $paymentService->cancelPayment($paymentInfo->externalOrderId);
        }
        $this->getLogger(__METHOD__)->debug(
            'translation.paymentEvent',
            [
                'hook' => $hook,
                'libResponse' => $libResponse,
                'paymentInfo' => $paymentInfo,
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
        $paymentService = $this->getPaymentService($orderId);
        $libResponse = $paymentService->ship($paymentInformation, $orderId);
        
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
        if ($paymentInformation->paymentMethod == PluginConfiguration::INVOICE
            || $paymentInformation->paymentMethod == PluginConfiguration::INVOICE_GUARANTEED
            || $paymentInformation->paymentMethod == PluginConfiguration::INVOICE_FACTORING
        ) {
            if (empty($paymentInformation->transaction)) {
                return;
            }
            $language = 'DE';
            foreach ($order->properties as $property) {
                if ($property->typeId === OrderPropertyType::DOCUMENT_LANGUAGE) {
                    $language = $property->value;
                }
            }
            /** @var Translator $translator */
            $translator = pluginApp(Translator::class);
            $text = implode(PHP_EOL, [
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.transferTo', [], $language),
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.iban', [], $language) . $paymentInformation->transaction['iban'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.bic', [], $language) . $paymentInformation->transaction['bic'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.holder', [], $language) . $paymentInformation->transaction['holder'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.shortId', [], $language) . $paymentInformation->transaction['shortId'],
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
