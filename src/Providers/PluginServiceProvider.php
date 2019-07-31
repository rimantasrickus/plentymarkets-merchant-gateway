<?php
namespace HeidelpayMGW\Providers;

use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Order\Pdf\Events\OrderPdfGenerationEvent;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Pdf\Models\OrderPdfGeneration;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Plugin\Translation\Translator;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Methods\InvoicePaymentMethod;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Providers\PluginRouteServiceProvider;
use HeidelpayMGW\Methods\InvoiceGuaranteedPaymentMethod;
use HeidelpayMGW\Methods\InvoiceGuaranteedPaymentMethodB2B;
use HeidelpayMGW\Repositories\PaymentInformationRepository;

/**
 * Class PluginServiceProvider
 * @package HeidelpayMGW\Providers
 */
class PluginServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
     * Register the service provider.
     */

    public function register()
    {
        $this->getApplication()->register(PluginRouteServiceProvider::class);
    }
    
    public function boot(
        EventProceduresService $eventProceduresService,
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $payContainer,
        SessionHelper $sessionHelper,
        Dispatcher $eventDispatcher,
        PaymentInformationRepository $paymentInformationRepository
    ) {
        $logger = $this->getLogger(__METHOD__);

        //Invoice
        $paymentHelper->createMopIfNotExists(PluginConfiguration::PAYMENT_KEY_INVOICE);
        $payContainer->register(
            PluginConfiguration::PLUGIN_KEY.'::'.PluginConfiguration::PAYMENT_KEY_INVOICE,
            InvoicePaymentMethod::class,
            [
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
            ]
        );
        //Invoice guaranteed B2C
        $paymentHelper->createMopIfNotExists(PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED);
        $payContainer->register(
            PluginConfiguration::PLUGIN_KEY.'::'.PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED,
            InvoiceGuaranteedPaymentMethod::class,
            [
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
            ]
        );
        //Invoice guaranteed B2B
        $paymentHelper->createMopIfNotExists(PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B);
        $payContainer->register(
            PluginConfiguration::PLUGIN_KEY.'::'.PluginConfiguration::PAYMENT_KEY_INVOICE_GUARANTEED_B2B,
            InvoiceGuaranteedPaymentMethodB2B::class,
            [
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
            ]
        );

        // Listen for the event that gets the payment method content
        $eventDispatcher->listen(
            GetPaymentMethodContent::class,
            function (GetPaymentMethodContent $event) use (
                $sessionHelper, $paymentHelper, $logger
            ) {
                try {
                    //skip not HeidelpayMGW payment
                    if (!$paymentHelper->isHeidelpayMGWMOP($event->getMop())) {
                        return $event->setType(GetPaymentMethodContent::RETURN_TYPE_CONTINUE);
                    }

                    $paymentType = $sessionHelper->getValue('paymentType');
                    if (!empty($paymentType)) {
                        // make a charge
                        $orderId = '';
                        $response = $paymentHelper->executeCharge($paymentType, $orderId, $event->getMop());
                        
                        $event->setValue($response['value']);
                        return $event->setType($response['type']);
                    }
                } catch (\Exception $e) {
                    $logger->exception(
                        'translation.exception',
                        [
                            'error' => $e->getMessage()
                        ]
                    );

                    $event->setValue('Unexpected error.');
                    return $event->setType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                }
            }
        );

        // Listen for the event that executes the payment
        $eventDispatcher->listen(
            ExecutePayment::class,
            function (ExecutePayment $event) use (
                $paymentHelper, $sessionHelper, $logger, $paymentInformationRepository
            ) {
                try {
                    // if payment method not ours, we don't care
                    if (!$paymentHelper->isHeidelpayMGWMOP($event->getMop())) {
                        return $event->setType(GetPaymentMethodContent::RETURN_TYPE_CONTINUE);
                    }
                    $payment = $sessionHelper->getValue('paymentInformation');
                    if (!empty($payment)) {
                        $paymentInformationRepository->updateOrderId($payment['paymentType'], (string)$event->getOrderId());
                        $paymentHelper->handlePayment($payment, $event->getOrderId(), $event->getMop());
                    }
                } catch (\Exception $e) {
                    $logger->exception(
                        'translation.exception',
                        [
                            'error' => $e->getMessage()
                        ]
                    );

                    $event->setValue('Unexpected error.');
                    return $event->setType('error');
                }
            }
        );

        // Handle document generation
        $eventDispatcher->listen(
            OrderPdfGenerationEvent::class,
            static function (OrderPdfGenerationEvent $event) use (
                $paymentHelper, $logger, $paymentInformationRepository
            ) {
                try {
                    $order = $event->getOrder();
                    
                    $docType = $event->getDocType();
                    $mopId = $order->methodOfPaymentId;
                    if (!$paymentHelper->isHeidelpayMGWMOP($mopId)) {
                        return;
                    }
                    $orderId = $order->typeId === 3 ? $order->parentOrder->id : $order->id;
                    $payments = $order->typeId === 3 ? $order->parentOrder->payments : $order->payments;
                    $paymentInformation = $paymentInformationRepository->getByOrderId($orderId);
                    if (empty($paymentInformation)) {
                        return ;
                    }

                    switch ($docType) {
                        case Document::INVOICE:
                            if ($paymentInformation->paymentMethod == 'invoice' || $paymentInformation->paymentMethod == 'invoice-guaranteed' || $paymentInformation->paymentMethod == 'invoice-factoring') {
                                if (empty($paymentInformation->transaction)) {
                                    return;
                                }
                                $language = 'DE';
                                foreach ($order->properties as $property) {
                                    if ($property->typeId === OrderPropertyType::DOCUMENT_LANGUAGE) {
                                        $language = $property->value;
                                    }
                                }
                                $translator = pluginApp(Translator::class);
                                $text = implode(PHP_EOL, [
                                    $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.transferTo', [], $language),
                                    $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.iban', [], $language) . $paymentInformation->transaction['iban'],
                                    $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.bic', [], $language) . $paymentInformation->transaction['bic'],
                                    $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.holder', [], $language) . $paymentInformation->transaction['holder'],
                                    $translator->trans(PluginConfiguration::PLUGIN_NAME.'::translation.descriptor', [], $language) . $paymentInformation->transaction['descriptor'],
                                ]);
                                // add payment information to the invoice pdf
                                $orderPdfGeneration           = pluginApp(OrderPdfGeneration::class);
                                $orderPdfGeneration->language = $language;
                                $orderPdfGeneration->advice   = $text;
                                $event->addOrderPdfGeneration($orderPdfGeneration);
                            }
                            break;
                        case Document::DELIVERY_NOTE:
                            // perform finalize transaction
                            if ($paymentInformation->paymentMethod == 'invoice' || $paymentInformation->paymentMethod == 'invoice-guaranteed' || $paymentInformation->paymentMethod == 'invoice-factoring') {
                                $invoiceId = '';
                                foreach ($order->documents as $document) {
                                    if ($document->type ==  Document::INVOICE) {
                                        $invoiceId = $document->numberWithPrefix;
                                    }
                                }
                                $paymentHelper->executeShipment(
                                    $orderId,
                                    $paymentInformation->transaction['paymentId'],
                                    $invoiceId
                                );
                                return;
                            }
                            break;
                        case Document::RETURN_NOTE:
                                // perform refund transaction
                                $paymentHelper->cancelCharge(
                                    $paymentInformation,
                                    $order->amounts[0]->invoiceTotal,
                                    (array)$payments,
                                    $orderId,
                                    $order->orderItems
                                );
                                return;
                            break;
                        default:
                        // do nothing
                        break;
                    }
                } catch (\Exception $e) {
                    $logger->exception(
                        'translation.exception',
                        [
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }
        );
    }
}
