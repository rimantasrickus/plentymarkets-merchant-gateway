<?php

namespace HeidelpayMGW\Migrations;

use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

use HeidelpayMGW\Models\InvoiceGuaranteedB2BSetting;

/**
 * Create InvoiceGuaranteedB2B model's table
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
 * @link https://docs.heidelpay.com/
 *
 * @package  heidelpayMGW/migrations
 *
 * @author Rimantas <development@heidelpay.com>
 */
class CreateInvoiceGuaranteedB2BSettingTable extends BasePluginMigration
{
    /**
     * Create InvoiceGuaranteedB2BSetting model's table
     *
     * @param Migrate $migrate
     *
     * @return void
     */
    public function run(Migrate $migrate)
    {
        $this->createTable($migrate, InvoiceGuaranteedB2BSetting::class);
    }
}
