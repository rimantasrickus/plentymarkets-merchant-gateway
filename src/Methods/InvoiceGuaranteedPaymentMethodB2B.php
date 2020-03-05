<?php

namespace HeidelpayMGW\Methods;

use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\InvoiceGuaranteedB2BSettingRepository;

/**
* Invoice guaranteed B2B payment method class
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
* @link  https://docs.heidelpay.com/
*
* @author  Rimantas  <development@heidelpay.com>
*
* @package  heidelpayMGW/methods
*/
class InvoiceGuaranteedPaymentMethodB2B extends BasePaymentMethod
{
    const AVAILABLE_COUNTRIES = ['DE', 'AT'];

    /**
     * InvoiceGuaranteedPaymentMethodB2B constructor
     * Provide our settings repository to base payment method
     */
    public function __construct()
    {
        parent::__construct(pluginApp(InvoiceGuaranteedB2BSettingRepository::class));
    }

    /**
     * Check whether the plugin should be active
     *
     * @return bool  Is payment method active for a checkout
     */
    public function isActive(): bool
    {
        if (!$this->basketService->isBasketB2B()) {
            return false;
        }
        
        return parent::isActive();
    }
}
