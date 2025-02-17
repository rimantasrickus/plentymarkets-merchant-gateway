<?php

namespace HeidelpayMGW\Methods;

use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\SepaDirectDebitGuaranteedSettingRepository;

/**
 * SEPA Direct Debit guaranteed payment method
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
 * @link https://docs.heidelpay.com/
 *
 * @package  heidelpayMGW/methods
 *
 * @author Rimantas <development@heidelpay.com>
 */
class SepaDirectDebitGuaranteedPaymentMethod extends BasePaymentMethod
{
    public function __construct()
    {
        parent::__construct(pluginApp(SepaDirectDebitGuaranteedSettingRepository::class));
    }

    /**
     * Check whether the plugin is active.
     *
     * @return bool
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
        return 'Heidelpay MGW SEPA Direct Debit Guaranteed';
    }
}
