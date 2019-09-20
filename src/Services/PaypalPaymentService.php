<?php
namespace HeidelpayMGW\Services;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Plugin\Translation\Translator;
use HeidelpayMGW\Models\PaymentInformation;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\PaypalSettingRepository;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

/**
 * Card payment service
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
class PaypalPaymentService extends AbstractPaymentService
{
    use Loggable;

    /** @var LibraryCallContract $libCall  Plenty LibraryCall */
    private $libCall;

    /** @var SessionHelper $sessionHelper  Saves information for current plugin session */
    private $sessionHelper;

    /** @var OrderHelper $orderHelper  Order manipulation with AuthHelper */
    private $orderHelper;

    /** @var PaypalSettingRepository $paypalSettings  Card settings repository*/
    private $paypalSettings;

    /** @var Translator $translator  Plenty Translator service */
    private $translator;

    /**
     * PaypalPaymentService constructor
     *
     * @param LibraryCallContract $libCall
     * @param SessionHelper $sessionHelper
     * @param OrderHelper $orderHelper
     * @param PaypalSettingRepository $paypalSettingRepository
     * @param Application $app
     */
    public function __construct(
        LibraryCallContract $libCall,
        SessionHelper $sessionHelper,
        OrderHelper $orderHelper,
        PaypalSettingRepository $paypalSettingRepository,
        Translator $translator
    ) {
        $this->libCall = $libCall;
        $this->sessionHelper = $sessionHelper;
        $this->orderHelper = $orderHelper;
        $this->paypalSettings = $paypalSettingRepository->get();
        $this->translator = $translator;

        parent::__construct();
    }

    /**
     * Make a charge call with HeidelpayMGW PHP-SDK
     *
     * @param array $payment
     *
     * @return array
     */
    public function charge(array $payment): array
    {
        $data = $this->prepareChargeRequest($payment);
        
        if ($this->paypalSettings->mode == PluginConfiguration::AUTHORIZATION_CAPTURE) {
            $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::authorisationCapture', $data);
        } else {
            $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::directDebit', $data);
        }
        
        $this->getLogger(__METHOD__)->debug(
            'translation.charge',
            [
                'paypalMode' => $this->paypalSettings->mode,
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
     * @param PaymentInformation $paymentInformation
     * @param Order $order
     *
     * @return array
     */
    public function cancelCharge(PaymentInformation $paymentInformation, Order $order): array
    {
        $data = parent::prepareCancelChargeRequest($paymentInformation, $order);
        if ($this->paypalSettings->mode == PluginConfiguration::AUTHORIZATION_CAPTURE) {
            $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelAutorization', $data);
            $commentText = $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAuthorization');
        } else {
            $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelCharge', $data);
            $commentText = $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAmount') . $data['amount'];
        }
        
        
        if (!empty($libResponse['merchantMessage'])) {
            $commentText = $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage'];

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

        $charge = $this->sessionHelper->getValue('paymentInformation')['transaction'];
        if (empty($charge)) {
            return;
        }
        
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentCompleted'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.shortId') . $charge['shortId'],
        ]);
        $this->createOrderComment($orderId, $commentText);
    }

    /**
     * Change payment status and add comment to Order
     *
     * @param string $externalOrderId
     *
     * @return bool
     */
    public function cancelPlentyPayment(string $externalOrderId): bool
    {
        try {
            $order = $this->orderHelper->findOrderByExternalOrderId($externalOrderId);
            parent::changePaymentStatusCanceled($order);

            $this->createOrderComment(
                $order->id,
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentCanceled')
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
     * Since we don't need to make ship call, we charge on reserved authorization
     *
     * @param PaymentInformation $paymentInformation
     * @param integer $orderId
     *
     * @return array
     */
    public function ship(PaymentInformation $paymentInformation, int $orderId): array
    {
        if ($this->paypalSettings->mode == PluginConfiguration::AUTHORIZATION_CAPTURE) {
            $order = $this->orderHelper->findOrderById($orderId);
            $this->chargeAuthorization($paymentInformation, $order);
        }

        return parent::ship($paymentInformation, $orderId);
    }

    /**
     * Charge reserved authorization in HeidelpayMGW
     *
     * @param PaymentInformation $paymentInformation
     * @param Order $order
     *
     * @return void
     */
    public function chargeAuthorization(PaymentInformation $paymentInformation, Order $order)
    {
        $amount = $order->amounts
            ->where('currency', '=', $paymentInformation->transaction['currency'])
            ->first()->invoiceTotal;

        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::chargeAuthorization', [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'paymentId' => $paymentInformation->transaction['paymentId'],
            'amount' => $amount
        ]);

        $commentText = $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successChargeAuthorization');
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                'translation.errorChargeAuthorization',
                [
                    'error' => $libResponse
                ]
            );
            $commentText = $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage'];
        }

        $this->createOrderComment($order->id, $commentText);

        $this->getLogger(__METHOD__)->debug(
            'translation.chargeAuthorization',
            [
                'order' => $order,
                'paymentId' => $paymentInformation->transaction['paymentId'],
                'amount' => $amount,
                'libResponse' => $libResponse
            ]
        );
    }
}
