<?php

namespace HeidelpayMGW\Services;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Plugin\Translation\Translator;
use HeidelpayMGW\Models\PaymentInformation;
use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

/**
 * iDEAL payment service
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
class IdealPaymentService extends AbstractPaymentService
{
    use Loggable;

    /** @var LibraryCallContract $libCall  Plenty LibraryCall */
    private $libCall;

    /** @var SessionHelper $sessionHelper  Saves information for current plugin session */
    private $sessionHelper;

    /** @var OrderHelper $orderHelper  Order manipulation with AuthHelper */
    private $orderHelper;

    /** @var Translator $translator  Plenty Translator service */
    private $translator;

    /**
     * IdealPaymentService constructor
     *
     * @param LibraryCallContract $libCall  Plenty LibraryCall
     * @param SessionHelper $sessionHelper  Saves information for current plugin session
     * @param OrderHelper $orderHelper  Order manipulation with AuthHelper
     * @param Translator $translator  Plenty Translator service
     */
    public function __construct(
        LibraryCallContract $libCall,
        SessionHelper $sessionHelper,
        OrderHelper $orderHelper,
        Translator $translator
    ) {
        $this->libCall = $libCall;
        $this->sessionHelper = $sessionHelper;
        $this->orderHelper = $orderHelper;
        $this->translator = $translator;

        parent::__construct();
    }

    /**
     * Make a charge call with HeidelpayMGW PHP-SDK
     *
     * @param array $payment  Payment type information from Frontend JS
     *
     * @return array  Payment information from SDK
     */
    public function charge(array $payment): array
    {
        $data = $this->prepareChargeRequest($payment);

        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::directDebit', $data);

        $this->getLogger(__METHOD__)->debug(
            'translation.charge',
            [
                'data' => $data,
                'libResponse' => $libResponse
            ]
        );
        
        return $libResponse;
    }

    /**
     * Prepare information for HeidelpayMGW charge call
     *
     * @param array $payment
     *
     * @return array
     */
    public function prepareChargeRequest(array $payment)
    {
        $data = parent::prepareChargeRequest($payment);
        $data['route'] = parent::getBaseUrl().'/'.PluginConfiguration::PLUGIN_NAME.'/process-redirect';
        
        return $data;
    }

    /**
     * Make API call to cancel charge
     *
     * @param PaymentInformation $paymentInformation  Heidelpay payment information
     * @param Order $order  Plenty Order
     *
     * @return array  Response from SDK
     */
    public function cancelCharge(PaymentInformation $paymentInformation, Order $order): array
    {
        $data = parent::prepareCancelChargeRequest($paymentInformation, $order);
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelCharge', $data);
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAmount') . $data['amount']
        ]);
        
        if (!empty($libResponse['merchantMessage'])) {
            $commentText = implode('<br />', [
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage']
            ]);

            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::translation.cancelChargeError',
                [
                    'data' => $data,
                    'libResponse' => $libResponse
                ]
            );
        }
        $this->createOrderComment($order->parentOrder->id, $commentText);

        $this->getLogger(__METHOD__)->debug(
            'translation.cancelCharge',
            [
                'data' => $data,
                'libResponse' => $libResponse
            ]
        );
        
        return $libResponse;
    }

    /**
     * Update plentymarkets Order with external Order ID and comment
     *
     * @param int $orderId
     * @param string $externalOrderId
     *
     * @return void
     */
    public function addExternalOrderId(int $orderId, string $externalOrderId)
    {
        parent::addExternalOrderId($orderId, $externalOrderId);
        /** @var array $transaction */
        $transaction = $this->sessionHelper->getValue('paymentInformation')['transaction'];
        if (empty($transaction)) {
            return;
        }
        /** @var string $commentText */
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentCompleted'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.shortId') . $transaction['shortId'],
        ]);
        $this->createOrderComment($orderId, $commentText);
    }

    /**
     * Change payment status and add comment to Order
     *
     * @param string $externalOrderId  Heidelpay Order ID
     *
     * @return bool  Was payment status changed
     */
    public function cancelPayment(string $externalOrderId): bool
    {
        try {
            $order = $this->orderHelper->findOrderByExternalOrderId($externalOrderId);
            parent::changePaymentStatusCanceled($order);

            $commentText = implode('<br />', [
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentCanceled')
            ]);
            $this->createOrderComment(
                $order->id,
                $commentText
            );
    
            return true;
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'log.exception',
                [
                    'message' => $e->getMessage()
                ]
            );

            return false;
        }
    }

    /**
     * Make API call ship to finalize transaction
     *
     * @param PaymentInformation $paymentInformation  Heidelpay payment information
     * @param integer $orderId  Plenty Order ID
     *
     * @return array
     */
    public function ship(PaymentInformation $paymentInformation, int $orderId): array
    {
        return parent::ship($paymentInformation, $orderId);
    }
}
