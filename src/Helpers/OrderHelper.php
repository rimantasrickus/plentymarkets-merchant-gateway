<?php

namespace HeidelpayMGW\Helpers;

use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Models\OrderType;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
* Helper class for Order manipulation with AuthHelper
*
* Copyright (C) 2020 heidelpay GmbH
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
* @link  https://docs.heidelpay.com/
*
* @author  Rimantas  <development@heidelpay.com>
*
* @package  heidelpayMGW/helpers
*/
class OrderHelper
{
    /** @var OrderRepositoryContract $orderRepository */
    private $orderRepository;

    /** @var AuthHelper $authHelper */
    private $authHelper;
    
    /**
     * OrderHelper constructor
     *
     * @param OrderRepositoryContract $orderRepository
     * @param AuthHelper $authHelper
     */
    public function __construct(OrderRepositoryContract $orderRepository, AuthHelper $authHelper)
    {
        $this->orderRepository = $orderRepository;
        $this->authHelper = $authHelper;
    }

    /**
     * Find Order by ID using AuthHelper
     *
     * @param int $orderId  Plenty Order ID
     *
     * @return Order
     *
     * @throws \Throwable
     */
    public function findOrderById(int $orderId): Order
    {
        return $this->authHelper->processUnguarded(
            function () use ($orderId) {
                return  $this->orderRepository->findOrderById($orderId);
            }
        );
    }

    /**
     * Update Order model
     *
     * @param array $order  Plenty Order model as array
     * @param int $orderId  Plenty Order ID
     *
     * @return Order
     *
     * @throws \Throwable
     */
    public function updateOrder(array $order, int $orderId): Order
    {
        return $this->authHelper->processUnguarded(
            function () use ($order, $orderId) {
                return  $this->orderRepository->updateOrder($order, $orderId);
            }
        );
    }

    /**
     * Return Order by external Order ID
     *
     * @param string $externalOrderId  heidelpay Order ID
     *
     * @return Order
     *
     * @throws \Throwable
     */
    public function findOrderByExternalOrderId(string $externalOrderId): Order
    {
        return $this->authHelper->processUnguarded(
            function () use ($externalOrderId) {
                return $this->orderRepository->findOrderByExternalOrderId($externalOrderId);
            }
        );
    }

    /**
     * Find child Credit note Order from sales Order
     *
     * @param Order $order
     *
     * @return Order|null
     */
    public function findCreditNoteOrderUnauthorized(Order $order)
    {
        return $this->authHelper->processUnguarded(
            function () use ($order) {
                return $order->childOrders->where('typeId', '=', OrderType::TYPE_CREDIT_NOTE)->first();
            }
        );
    }
    
    /**
     * Return original order ID
     *
     * @param Order $order
     *
     * @return int
     */
    public function getOriginalOrderId(Order $order): int
    {
        return $this->getOriginalOrder($order)->id;
    }

    /**
     * Return Original Order
     *
     * @param Order $order
     *
     * @return Order
     */
    public function getOriginalOrder(Order $order): Order
    {
        if ($order->typeId === OrderType::TYPE_SALES_ORDER) {
            return $order;
        }
        
        while ($order->parentOrder) {
            if ($order->parentOrder->typeId === OrderType::TYPE_SALES_ORDER) {
                return $order->parentOrder;
            }
            $order = $order->parentOrder;
        }

        return $order;
    }
}
