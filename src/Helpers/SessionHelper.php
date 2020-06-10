<?php

namespace HeidelpayMGW\Helpers;

use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;

use HeidelpayMGW\Configuration\PluginConfiguration;

/**
 * Saves information for current plugin session
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
 * @package  heidelpayMGW/helpers
 *
 * @author Rimantas <development@heidelpay.com>
 */
class SessionHelper
{
    /** @var FrontendSessionStorageFactoryContract $sessionStorage */
    private $sessionStorage;
    
    /**
     * SessionHelper construction
     *
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     */
    public function __construct(FrontendSessionStorageFactoryContract $sessionStorage)
    {
        $this->sessionStorage = $sessionStorage;
    }
    
    /**
     * Set the session value
     *
     * @param string $key  Key of saved information
     * @param mixed $value  Information to save
     *
     * @return void
     */
    public function setValue(string $key, $value)
    {
        $this->sessionStorage->getPlugin()->setValue(PluginConfiguration::PLUGIN_NAME.'_'.$key, $value);
    }
    
    /**
     * Get the session value
     *
     * @param string $key  Key of saved information
     *
     * @return mixed
     */
    public function getValue(string $key)
    {
        return $this->sessionStorage->getPlugin()->getValue(PluginConfiguration::PLUGIN_NAME.'_'.$key);
    }
}
