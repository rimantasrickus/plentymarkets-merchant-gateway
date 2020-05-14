<?php

namespace HeidelpayMGW\Helpers;

use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable as PlentyLogger;

/**
* Helper class for easier logging in Plentysystem
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
class Logger
{
    use PlentyLogger;
   
    /** @var string $identifier */
    private $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Add debug level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function debug(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->debug(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    /**
     * Add info level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function info(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->info(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    /**
     * Add error level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function error(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->error($message, $value);
    }

    /**
     * Add notice level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function notice(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->notice(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    /**
     * Add warning level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function warning(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->warning(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    /**
     * Add error level log
     *
     * @param string $message  Entry's title
     * @param mixed|null $value Entry's value
     *
     * @return void
     */
    public function exception(string $message, $value = null)
    {
        $this->getLogger($this->identifier)->error(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }
}
