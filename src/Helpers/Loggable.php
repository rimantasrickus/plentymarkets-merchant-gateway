<?php

namespace HeidelpayMGW\Helpers;

/**
* Returns logger
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
* @package  heidelpayMGW/helpers
*/
trait Loggable
{
    /**
     * Return Logger class
     *
     * @param string $identifier  To identify log entry in the Plenty log list
     *
     * @return Logger
     */
    public function getLogger($identifier): Logger
    {
        return pluginApp(Logger::class, [$identifier]);
    }
}
