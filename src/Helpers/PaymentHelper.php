<?php
namespace Heidelpay\Helpers;

use Plenty\Modules\Payment\Contracts\PaymentContactRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Order\RelationReference\Models\OrderRelationReference;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Payment\Models\PaymentContactRelation;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Modules\Account\Contact\Models\Contact;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Order\Models\Order;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Helpers\OrderHelper;
use Heidelpay\Helpers\SessionHelper;
use Heidelpay\Models\PaymentInformation;
use Heidelpay\Services\InvoicePaymentService;
use Heidelpay\Configuration\PluginConfiguration;
use Heidelpay\Services\InvoiceGuaranteedPaymentService;
use Heidelpay\Repositories\PaymentInformationRepository;
use Heidelpay\Services\InvoiceGuaranteedPaymentServiceB2B;

class PaymentHelper
{
    use Loggable;

    // payment events
    const PAYMENT_COMPLETED = 'payment.completed';
    const PAYMENT_CANCELED = 'payment.canceled';
    const PAYMENT_PARTLY = 'payment.partly';
    const PAYMENT_PENDING = 'payment.pending';

    private $paymentMethodRepository;
    private $sessionHelper;
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
     * @param string $payment
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
     * @param string $payment
     *
     * @return int
     */
    public function getPaymentMethod(string $payment)
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
     * Get payment method list
     *
     * @return array
     */
    public function getPaymentMethodList()
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(PluginConfiguration::PLUGIN_KEY);
        
        $mopList = array();
        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $mop) {
                $mopList[] = [
                    'id' => $mop->id,
                    'paymentKey' => $mop->paymentKey,
                ];
            }
        }

        return $mopList;
    }

    /**
     * Check if mop ID is Heidelpay
     *
     * @param int $mopId
     *
     * @return boolean
     */
    public function isHeidelpayMOP($mopId)
    {
        $mopList = $this->getPaymentMethodList();
        foreach ($mopList as $mop) {
            if ($mop['id'] == $mopId) {
                return true;
            }
        }

        return false;
    }

    public function executeCharge(array $paymentType, string $orderId, int $mopId)
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
            'orderId' => $orderId,
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

    public function handlePayment(array $payment, int $orderId, int $mopId)
    {
        $paymentService = $this->getPaymentService($orderId);
        $externalOrderId = $this->sessionHelper->getValue('externalOrderId');
        $referenceNumber = '';
        // handle invoice payment
        if ($payment['paymentMethod'] == 'invoice' || $payment['paymentMethod'] == 'invoice-guaranteed' || $payment['paymentMethod'] == 'invoice-factoring') {
            $referenceNumber = $payment['transaction']['descriptor'];
        }
        // if payment completed add amount to payment
        $amount = 0.00;
        if ($payment['transaction']['status'] == 'completed') {
            $amount = (float)$payment['transaction']['amount'];
        }

        // add external Order ID and invoice comment to Order
        $paymentService->updateOrder($orderId, $externalOrderId);
        $payment = $paymentService->addPaymentToOrder($orderId, $referenceNumber, $mopId, $amount, $payment['transaction']['currency']);
        $paymentService->assignPaymentToContact($payment, $orderId);
    }

    public function cancelCharge(PaymentInformation $paymentInformation, float $invoiceTotal, array $payments, int $orderId, $orderItems)
    {
        if ($paymentInformation->paymentMethod == 'invoice' || $paymentInformation->paymentMethod == 'invoice-guaranteed' || $paymentInformation->paymentMethod == 'invoice-factoring') {
            if (empty($paymentInformation->transaction)) {
                return;
            }
            $paymentService = $this->getPaymentService($orderId);
            $paymentService->cancelCharge(
                $paymentInformation->transaction['paymentId'],
                $paymentInformation->transaction['chargeId'],
                $invoiceTotal,
                (array)$payments,
                $orderId,
                $paymentInformation->paymentMethod,
                $orderItems
            );
        }
    }

    public function handleWebhook(array $hook, array $libResponse)
    {
        if ($hook['event'] == self::PAYMENT_PENDING) {
            return true;
        }
        $paymentInfo = null;
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
            $updated = $paymentService->updatePayedAmount((int)$paymentInfo->orderId, (int)($libResponse['total'] * 100), Payment::STATUS_CAPTURED);
        }
        // payment completed logic
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
                'updated' => $updated,
            ]
        );

        return $updated;
    }

    public function executeShipment(int $orderId, string $paymentId, string $invoiceId)
    {
        $paymentService = $this->getPaymentService($orderId);
        $libResponse = $paymentService->ship($paymentId, $invoiceId, $orderId);
        
        $this->getLogger(__METHOD__)->debug(
            'translation.executeShipment',
            [
                'orderId' => $orderId,
                'paymentId' => $paymentId,
                'invoiceId' => $invoiceId,
                'libResponse' => $libResponse
            ]
        );
    }
}
