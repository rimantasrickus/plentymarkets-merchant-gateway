<?php

namespace HeidelpayMGW\Methods;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\SofortSettingRepository;

/**
* Sofort payment method class
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
* @package  heidelpayMGW/methods
*/
class SofortPaymentMethod extends BasePaymentMethod
{
    use Loggable;

    const AVAILABLE_COUNTRIES = ['DE'];

    /**
     * SofortPaymentMethod constructor
     * Provide our settings repository to base payment method
     */
    public function __construct()
    {
        parent::__construct(pluginApp(SofortSettingRepository::class));
    }

    /**
     * Check whether the plugin should be active
     *
     * @return bool  Is payment method active for a checkout
     */
    public function isActive(): bool
    {
        return parent::isActive();
    }

    /**
     * @param string $lang
     * @return string
     */
    public function getBackendName(string $lang = ""): string
    {
        return 'Heidelpay MGW SOFORT';
    }
}
