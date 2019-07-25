<?php
namespace Heidelpay\Helpers;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;

use Heidelpay\Configuration\PluginConfiguration;

class OrderHelper
{
    private $orderRepository;
    private $authHelper;
    
    public function __construct(OrderRepositoryContract $orderRepository, AuthHelper $authHelper)
    {
        $this->orderRepository = $orderRepository;
        $this->authHelper = $authHelper;
    }

    public function findOrderById(int $orderId)
    {
        return $this->authHelper->processUnguarded(
            function () use ($orderId) {
                return  $this->orderRepository->findOrderById($orderId);
            }
        );
    }

    public function updateOrder(array $order, int $orderId)
    {
        $this->authHelper->processUnguarded(
            function () use ($order, $orderId) {
                return  $this->orderRepository->updateOrder($order, $orderId);
            }
        );
    }

    public function findOrderByExternalOrderId(string $externalOrderId)
    {
        return $this->authHelper->processUnguarded(
            function () use ($externalOrderId) {
                return $this->orderRepository->findOrderByExternalOrderId($externalOrderId);
            }
        );
    }
}
