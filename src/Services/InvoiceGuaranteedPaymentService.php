<?php

namespace HeidelpayMGW\Services;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use HeidelpayMGW\Helpers\SessionHelper;
use Plenty\Modules\Order\Models\Order;
use HeidelpayMGW\Models\PaymentInformation;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Document\Models\Document;
use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use HeidelpayMGW\Repositories\InvoiceGuaranteedSettingRepository;

class InvoiceGuaranteedPaymentService extends AbstractPaymentService
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
     * InvoiceGuaranteedPaymentService constructor
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
     * Make a charge call with Heidelpay PHP-SDK
     *
     * @param array $payment  Payment type information from Frontend JS
     *
     * @return array  Payment information from SDK
     */
    public function charge(array $payment): array
    {
        $data = parent::prepareChargeRequest($payment);

        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::invoiceGuaranteed', $data);
        
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

        if ($paymentInformation->paymentMethod === PluginConfiguration::INVOICE_FACTORING) {
            $invoiceGuaranteedSettingRepo = pluginApp(InvoiceGuaranteedSettingRepository::class);
            $reason = '';
            foreach ($order->orderItems as $item) {
                foreach ($item->properties as $property) {
                    if ($property->typeId === OrderPropertyType::RETURNS_REASON) {
                        $reason = $invoiceGuaranteedSettingRepo->getReturnCode($property->value);
                    }
                }
            }
            $data['reason'] = $reason;
        }

        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelCharge', $data);

        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAmount') . $data['amount']
        ]);
        if (!empty($libResponse['merchantMessage'])) {
            $commentText = implode('<br />', [
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
                $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.cancelChargeError'),
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
     * @param int $orderId  Plenty Order ID
     * @param string $externalOrderId  Heidelpay Order ID
     *
     * @return void
     */
    public function updateOrder(int $orderId, string $externalOrderId)
    {
        parent::updateOrder($orderId, $externalOrderId);

        $charge = $this->sessionHelper->getValue('paymentInformation')['transaction'];
        if (empty($charge)) {
            return;
        }
        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.transferTo'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.iban') . $charge['iban'],
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.bic') . $charge['bic'],
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.holder') . $charge['holder'],
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.descriptor') . $charge['descriptor']
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
        $order = $this->orderHelper->findOrderById($orderId);
        $invoiceId = '';
        foreach ($order->documents as $document) {
            if ($document->type ===  Document::INVOICE) {
                $invoiceId = $document->numberWithPrefix;
            }
        }
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

        $commentText = implode('<br />', [
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.addedByPlugin'),
            $this->translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successShip')
        ]);

        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'translation.errorShip',
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
