<?php

namespace HeidelpayMGW\Methods;

use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\IdealSettingRepository;

/**
* iDEAL payment method class
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
class IdealPaymentMethod extends BasePaymentMethod
{
    const AVAILABLE_COUNTRIES = ['NL'];

    /**
     * IdealPaymentMethod constructor
     * Provide our settings repository to base payment method
     */
    public function __construct()
    {
        parent::__construct(pluginApp(IdealSettingRepository::class));
    }

    /**
     * Check whether the plugin should be active
     *
     * @return bool  Is payment method active for a checkout
     */
    public function isActive(): bool
    {
        if ($this->basketService->isBasketB2b()) {
            return false;
        }
        if ($this->isCountryRestricted()) {
            return false;
        }
        
        return parent::isActive();
    }

    /**
     * @param string $lang
     * @return string
     */
    public function getBackendName(string $lang = ""): string
    {
        return 'Heidelpay MGW iDEAL';
    }

    /**
     * Check if country of the address is in available countries list
     *
     * @return bool  True if not in the white list
     */
    private function isCountryRestricted(): bool
    {
        $address = $this->basketService->getCustomerAddressData()['billing'];
        if (in_array($address->country->isoCode2, self::AVAILABLE_COUNTRIES)) {
            return false;
        }

        return true;
    }
}
