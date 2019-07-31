<?php
namespace HeidelpayMGW\Services;

use Plenty\Modules\Payment\Contracts\PaymentContactRelationRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreConfigurationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Order\RelationReference\Models\OrderRelationReference;
use Plenty\Modules\Account\Contact\Contracts\ContactRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Models\PaymentContactRelation;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Account\Contact\Models\Contact;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Models\Order;
use Plenty\Plugin\Application;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\OrderHelper;
use HeidelpayMGW\Helpers\PaymentHelper;

abstract class AbstractPaymentService
{
    use Loggable;

    private $contactRepository;
    private $webstoreConfigurationRepository;
    private $orderRepository;
    private $authHelper;
    private $orderHelper;

    public function __construct()
    {
        $this->contactRepository = pluginApp(ContactRepositoryContract::class);
        $this->webstoreConfigurationRepository = pluginApp(WebstoreConfigurationRepositoryContract::class);
        $this->orderRepository = pluginApp(OrderRepositoryContract::class);
        $this->authHelper = pluginApp(AuthHelper::class);
        $this->orderHelper = pluginApp(OrderHelper::class);
    }

    abstract public function charge(array $payment);

    abstract public function cancelCharge(string $paymentId, string $chargeId, float $amount, array $payments, int $orderId, string $paymentMethod = null, $orderItems = null);

    public function generateExternalOrderId(int $id)
    {
        return uniqid($id . '.', true);
    }

    public function contactInformation(Address $address)
    {
        return [
            'firstName' => $address->firstName,
            'lastName' => $address->lastName,
            'email' => $address->email,
            'birthday' => $address->birthday,
            'phone' => $address->phone,
            'mobile' => $address->personalNumber,
            'gender' => $address->gender
        ];
    }

    abstract public function prepareRequest(Basket $basket, array $payment, array $addresses, string $externalOrderId);

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
    }

    /**
     * Update plentymarkets Order with external Order ID
     *
     * @param int $orderId
     * @param string $externalOrderId
     *
     * @return void
     */
    public function updateOrderExternalId(int $orderId, string $externalOrderId)
    {
        $order = $this->orderRepository->findOrderById($orderId);

        $externalOrder = pluginApp(OrderProperty::class);
        $externalOrder->typeId = OrderPropertyType::EXTERNAL_ORDER_ID;
        $externalOrder->value = $externalOrderId;
        $order->properties[] = $externalOrder;

        $this->orderRepository->updateOrder($order->toArray(), $orderId);
    }

    /**
     * Add HeidelpayMGW payment to Order
     *
     * @param int $orderId
     * @param string $referenceNumber
     * @param int $mopId
     *
     * @return void
     */
    public function addPaymentToOrder(int $orderId, string $referenceNumber, int $mopId, float $amount, string $currency)
    {
        try {
            $order = $this->orderHelper->findOrderById($orderId);

            $payment = $this->createPlentyPayment($mopId, $referenceNumber, $order, $amount, $currency);
            if ($payment instanceof Payment) {
                $paymentOrderRelationRepo = pluginApp(PaymentOrderRelationRepositoryContract::class);
                $orderRelation = $this->authHelper->processUnguarded(
                    function () use ($paymentOrderRelationRepo, $payment, $order) {
                        return  $paymentOrderRelationRepo->createOrderRelation($payment, $order);
                    }
                );

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
    }

    /**
     * Assign Payment to Contact
     *
     * @param Payment $payment
     * @param int $orderId
     *
     * @return bool
     */
    public function assignPaymentToContact(Payment $payment, int $orderId)
    {
        $order = $this->authHelper->processUnguarded(
            function () use ($orderId) {
                return  $this->orderRepository->findOrderById($orderId);
            }
        );

        $contactId = null;
        if (isset($order->relations)) {
            $contactId = $order->relations
                ->where('referenceType', OrderRelationReference::REFERENCE_TYPE_CONTACT)
                ->first()->referenceId;

            if (!empty($contactId)) {
                $contact = $this->authHelper->processUnguarded(
                    function () use ($contactId) {
                        return  $this->contactRepository->findContactById($contactId);
                    }
                );
                if ($contact instanceof Contact) {
                    $paymentContactRelationRepo = pluginApp(PaymentContactRelationRepositoryContract::class);
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
     * @param int $mopId
     * @param string $referenceNumber
     * @param Order $order
     *
     * @return Payment|null
     */
    public function createPlentyPayment(int $mopId, string $referenceNumber, Order $order, float $amount, string $currency)
    {
        try {
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
            $paymentProperties[] = $this->getPaymentProperty(PaymentProperty::TYPE_ORIGIN, Payment::ORIGIN_PLUGIN);
            $payment->properties = $paymentProperties;

            $paymentRepository = pluginApp(PaymentRepositoryContract::class);
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
    }

    /**
     * Get payment status for Plentymarkets payment
     *
     * @param integer $orderId
     * @param float $amount
     * @param string $paymentCurrency
     *
     * @return string
     */
    private function getPaymentStatus(Order $order, float $amount, string $paymentCurrency)
    {
        $orderAmount = $order->amounts->where('currency', '=', $paymentCurrency)->first();
        
        $paymentStatus = Payment::STATUS_AWAITING_APPROVAL;
        if ($orderAmount->invoiceTotal == $amount && $amount != 0) {
            $paymentStatus = Payment::STATUS_CAPTURED;
        }
        if ($orderAmount->invoiceTotal > $amount && $amount != 0) {
            $paymentStatus = Payment::STATUS_PARTIALLY_CAPTURED;
        }

        return $paymentStatus;
    }

    /**
     * Return PaymentProperty with given values
     *
     * @param int $typeId
     * @param string $value
     *
     * @return PaymentProperty
     */
    private function getPaymentProperty(int $typeId, string $value)
    {
        $paymentProperty = pluginApp(PaymentProperty::class);
        $paymentProperty->typeId = $typeId;
        $paymentProperty->value = $value;

        return $paymentProperty;
    }

    /**
     * Add comment to Order
     *
     * @param int $orderId
     * @param string $commentText
     *
     * @return void
     */
    public function createOrderComment(int $orderId, string $commentText)
    {
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
     * @return string
     */
    public function getBaseUrl()
    {
        $webstore = $this->webstoreConfigurationRepository->findByPlentyId(pluginApp(Application::class)->getPlentyId());

        return ($webstore->domainSsl ?? $webstore->domain);
    }

    /**
     * Update Payment amount
     *
     * @param int $orderId
     * @param int $amount
     *
     * @return bool
     */
    public function updatePayedAmount(int $orderId, int $amount, int $paymentStatus)
    {
        try {
            $paymentRepository = pluginApp(PaymentRepositoryContract::class);
            $payments = $this->authHelper->processUnguarded(
                function () use ($orderId, $paymentRepository) {
                    return $paymentRepository->getPaymentsByOrderId($orderId);
                }
            );
            
            $paymentHelper = pluginApp(PaymentHelper::class);
            foreach ($payments as $payment) {
                if ($paymentHelper->isHeidelpayMGWMOP($payment->mopId)) {
                    $payment->amount = $amount / 100;
                    $payment->status = $paymentStatus;
                    $payment->hash = $orderId.'-'.time();
                    $payment->updateOrderPaymentStatus = true;
                    
                    $this->authHelper->processUnguarded(
                        function () use ($payment, $paymentRepository) {
                            return  $paymentRepository->updatePayment($payment);
                        }
                    );
                    $this->assignPaymentToContact($payment, $orderId);
                }
            }
    
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

    abstract public function cancelPayment(string $externalOrderId);
}
