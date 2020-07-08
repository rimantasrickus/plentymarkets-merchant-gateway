<?php

namespace HeidelpayMGW\Methods;

use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Services\BasketService;
use Plenty\Modules\Payment\Method\Services\PaymentMethodBaseService;
use Plenty\Plugin\Application;
use Plenty\Plugin\Translation\Translator;

/**
* Base payment method class
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
class BasePaymentMethod extends PaymentMethodBaseService
{
    const AVAILABLE_COUNTRIES = [];

    /** @var mixed $settings  Settings model of a payment method */
    private $settings;

    /** @var BasketService $basketService */
    protected $basketService;

    /** @var Translator $translator */
    protected $translator;

    /**
     * BasePaymentMethod constructor
     *
     * @param mixed $settingRepository  Settings repository of plugin's payment method
     */
    public function __construct($settingRepository)
    {
        $this->settings = $settingRepository->get();
        $this->basketService = pluginApp(BasketService::class);
        $this->translator = pluginApp(Translator::class);
    }

    /**
     * Check whether the plugin should be active
     *
     * @return bool  Is payment method active for a checkout
     */
    public function isActive(): bool
    {
        if (!$this->settings->isActive) {
            return false;
        }

        $basket = $this->basketService->getBasket();
        if ($this->settings->basketMinTotal > 0.00 && $basket->basketAmount < $this->settings->basketMinTotal) {
            return false;
        }
        if ($this->settings->basketMaxTotal > 0.00 && $basket->basketAmount > $this->settings->basketMaxTotal) {
            return false;
        }
        if ($this->isCountryRestricted()) {
            return false;
        }

        return true;
    }

    /**
     * Get the name of the payment method
     *
     * @param string $lang
     * @return string  Payment method's name in checkout or PDF document
     */
    public function getName(string $lang = ""): string
    {
        return $this->settings->displayName;
    }

    /**
     * Get additional costs for heidelpay
     *
     * @return float
     */
    public function getFee(): float
    {
        return 0.00;
    }

    /**
     * Get the path of the icon
     *
     * @param string $lang
     * @return string  Icon path to display in checkout
     */
    public function getIcon(string $lang = ""): string
    {
        $app = pluginApp(Application::class);
        
        return $this->settings->iconURL ?: $app->getUrlPath(PluginConfiguration::PLUGIN_NAME) .
            '/images/default_payment_icon.png';
    }

    /**
     * Get the description of the payment method
     * Child class should implement it's own method
     *
     * @param string $lang
     * @return string
     */
    public function getDescription(string $lang = ""): string
    {
        return '';
    }

    /**
     * Check if country of the address is in available countries list
     *
     * @return bool  True if not in the white list
     */
    private function isCountryRestricted(): bool
    {
        $address = $this->basketService->getCustomerAddressData()['billing'];
        if (empty(static::AVAILABLE_COUNTRIES) || in_array($address->country->isoCode2, static::AVAILABLE_COUNTRIES)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSourceUrl(string $lang = ""): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function isSwitchableTo(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSwitchableFrom(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isBackendSearchable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isBackendActive(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getBackendName(string $lang = ""): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function canHandleSubscriptions(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getBackendIcon(): string
    {
        return '';
    }
}
