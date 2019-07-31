<?php
namespace HeidelpayMGW\Services;

use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Models\Order;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Services\BasketService;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\PluginSettingRepository;

class InvoicePaymentService extends AbstractPaymentService
{
    use Loggable;

    private $pluginSettings;
    private $libCall;
    private $addressRepository;
    private $contactRepository;
    private $sessionHelper;
    private $basketService;
    private $orderHelper;

    public function __construct(
        PluginSettingRepository $pluginSettingRepository,
        LibraryCallContract $libCall,
        AddressRepositoryContract $addressRepository,
        ContactRepositoryContract $contactRepository,
        SessionHelper $sessionHelper,
        BasketService $basketService,
        OrderHelper $orderHelper
    ) {
        $this->pluginSettings = $pluginSettingRepository->get();
        $this->libCall = $libCall;
        $this->addressRepository = $addressRepository;
        $this->contactRepository = $contactRepository;
        $this->sessionHelper = $sessionHelper;
        $this->basketService = $basketService;
        $this->orderHelper = $orderHelper;

        parent::__construct();
    }

    /**
     * Make a charge call with HeidelpayMGW PHP-SDK
     *
     * @param array $payment
     *
     * @return string
     */
    public function charge(array $payment)
    {
        $basket = $this->basketService->getBasket();
        $addresses = $this->basketService->getCustomerAddressData();
        $addresses['billing']['countryCode'] = $this->basketService->getCountryCode((int)$addresses['billing']->countryId);
        $addresses['billing']['stateName'] = $this->basketService->getCountryState((int)$addresses['billing']->countryId, (int)$addresses['billing']->stateId);
        $addresses['shipping']['countryCode'] = $this->basketService->getCountryCode((int)$addresses['shipping']->countryId);
        $addresses['shipping']['stateName'] = $this->basketService->getCountryState((int)$addresses['shipping']->countryId, (int)$addresses['shipping']->stateId);
        $externalOrderId = $this->generateExternalOrderId($basket->id);
        $this->sessionHelper->setValue('externalOrderId', $externalOrderId);

        $data = array();
        $libResponse = array();
        $data = $this->prepareRequest($basket, $payment, $addresses, $externalOrderId);
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::invoice', $data);

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
     * @param string $paymentId
     * @param string $chargeId
     * @param float $amount
     * @param array $payments
     * @param int $orderId
     *
     * @return array
     */
    public function cancelCharge(string $paymentId, string $chargeId, float $amount, array $payments, int $orderId, string $paymentMethod = null, $orderItems = null)
    {
        $amountSum = 0;
        $paymentHelper = pluginApp(PaymentHelper::class);
        foreach ($payments as $payment) {
            if ($paymentHelper->isHeidelpayMGWMOP($payment->mopId)) {
                $amountSum += $payment->amount;
            }
        }
        if ($amountSum < $amount) {
            $amount = $amountSum;
        }
        $data = [
            'privateKey' => $this->pluginSettings->privateKey,
            'paymentId' => $paymentId,
            'chargeId' => $chargeId,
            'amount' => $amount
        ];
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::cancelCharge', $data);

        $translator = pluginApp(Translator::class);
        $commentText = implode('<br />', [
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successCancelAmount') . $amount,
        ]);
        if (!empty($libResponse['merchantMessage'])) {
            $commentText = implode('<br />', [
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage'],
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.clientMessage') . $libResponse['clientMessage'],
            ]);

            $this->getLogger(__METHOD__)->error(
                PluginConfiguration::PLUGIN_NAME.'::translation.cancelChargeError',
                [
                    'data' => $data,
                    'libResponse' => $libResponse
                ]
            );
        }
        $this->createOrderComment($orderId, $commentText);

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
     * Prepare information for Invoice charge call
     *
     * @param Basket $basket
     * @param array $payment
     * @param Address $addresses
     * @param string $externalOrderId
     *
     * @return array
     */
    public function prepareRequest(Basket $basket, array $payment, array $addresses, string $externalOrderId)
    {
        return [
            'privateKey' => $this->pluginSettings->privateKey,
            'baseUrl' => $this->getBaseUrl(),
            'routeName' => PluginConfiguration::PLUGIN_NAME,
            'basket' => $basket,
            'invoiceAddress' => $addresses['billing'],
            'deliveryAddress' => $addresses['shipping'],
            'contact' => $this->contactInformation($addresses['billing']),
            'orderId' => $externalOrderId,
            'paymentType' => $payment,
            'metadata' => [
                'shopType' => 'Plentymarkets',
                'shopVersion' => '7',
                'pluginVersion' => PluginConfiguration::PLUGIN_VERSION,
                'pluginType' => 'plugin-HeidelpayMGW',
            ]
        ];
    }

    /**
     * Update plentymarkets Order with external Order ID and comment
     *
     * @param int $orderId
     * @param string $externalOrderId
     *
     * @return void
     */
    public function updateOrder(int $orderId, string $externalOrderId)
    {
        $order = $this->orderHelper->findOrderById($orderId);

        $externalOrder = pluginApp(OrderProperty::class);
        $externalOrder->typeId = OrderPropertyType::EXTERNAL_ORDER_ID;
        $externalOrder->value = $externalOrderId;
        $order->properties[] = $externalOrder;

        $this->orderHelper->updateOrder($order->toArray(), $orderId);

        $charge = $this->sessionHelper->getValue('paymentInformation')['transaction'];
        if (empty($charge)) {
            return;
        }
        $translator = pluginApp(Translator::class);
        $commentText = implode('<br />', [
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.transferTo'),
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.iban') . $charge['iban'],
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.bic') . $charge['bic'],
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.holder') . $charge['holder'],
            $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.descriptor') . $charge['descriptor'],
        ]);
        $this->createOrderComment($orderId, $commentText);
    }

    /**
     * Change payment status to canceled
     *
     * @param string $externalOrderId
     *
     * @return boolean
     */
    public function cancelPayment(string $externalOrderId)
    {
        try {
            $order = $this->orderHelper->findOrderByExternalOrderId($externalOrderId);
            $orderId = $order->id;
            $authHelper = pluginApp(AuthHelper::class);
            $paymentRepository = pluginApp(PaymentRepositoryContract::class);
            $payments = $authHelper->processUnguarded(
                function () use ($orderId, $paymentRepository) {
                    return $paymentRepository->getPaymentsByOrderId($orderId);
                }
            );

            $paymentHelper = pluginApp(PaymentHelper::class);
            foreach ($payments as $payment) {
                if ($paymentHelper->isHeidelpayMGWMOP($payment->mopId)) {
                    $payment->status = Payment::STATUS_CANCELED;
                    $payment->hash = $orderId.'-'.time();
                    $payment->updateOrderPaymentStatus = true;
                    
                    $updated = $authHelper->processUnguarded(
                        function () use ($payment, $paymentRepository) {
                            return  $paymentRepository->updatePayment($payment);
                        }
                    );
                    $this->assignPaymentToContact($payment, $orderId);
                }
            }

            $translator = pluginApp(Translator::class);
            $this->createOrderComment(
                $orderId,
                $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.paymentCanceled')
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

    public function ship(string $paymentId, string $invoiceId, int $orderId)
    {
        $libResponse = $this->libCall->call(PluginConfiguration::PLUGIN_NAME.'::invoiceShip', [
            'privateKey' => $this->pluginSettings->privateKey,
            'paymentId' => $paymentId,
            'invoiceId' => $invoiceId,
        ]);

        $translator = pluginApp(Translator::class);
        $commentText = $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.successShip');
        
        if (!$libResponse['success']) {
            $this->getLogger(__METHOD__)->error(
                'translation.errorShip',
                [
                    'error' => $libResponse
                ]
            );

            $commentText = $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.merchantMessage') . $libResponse['merchantMessage'];
        }

        $this->createOrderComment($orderId, $commentText);

        return $libResponse;
    }
}
