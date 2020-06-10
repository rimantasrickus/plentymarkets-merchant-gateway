<?php

namespace HeidelpayMGW\Migrations;

use HeidelpayMGW\Models\SepaDirectDebitSetting;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

/**
 * SEPA Direct Debit settings table migration
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
 * @package  heidelpayMGW/
 *
 * @author Rimantas <development@heidelpay.com>
 */
class CreateSepaDirectDebitSettingTable extends BasePluginMigration
{
    /**
     * Create SepaDirectDebitSetting model's table
     *
     * @param Migrate $migrate
     *
     * @return void
     */
    public function run(Migrate $migrate)
    {
        $this->createTable($migrate, SepaDirectDebitSetting::class);
    }
}
