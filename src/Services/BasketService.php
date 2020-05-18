<?php

namespace HeidelpayMGW\Services;

use HeidelpayMGW\Helpers\Loggable;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;

/**
 * BasketService class
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
 * @package  heidelpayMGW/services
 *
 * @author Rimantas <development@heidelpay.com>
 */
class BasketService
{
    use Loggable;

    /** @var AuthHelper $authHelper  Plenty AuthHelper */
    private $authHelper;

    /** @var AddressRepositoryContract $addressRepo  Plenty AddressRepository */
    private $addressRepo;

    /** @var BasketRepositoryContract $basketRepo  Plenty BasketRepository */
    private $basketRepo;

    /** @var CountryRepositoryContract $countryRepository  Plenty CountryRepository */
    private $countryRepository;

    /**
     * BasketService constructor
     *
     * @param CountryRepositoryContract $countryRepository  Plenty CountryRepository
     * @param AddressRepositoryContract $addressRepository  Plenty AddressRepository
     * @param BasketRepositoryContract $basketRepo  Plenty BasketRepository
     * @param AuthHelper $authHelper  Plenty AuthHelper
     */
    public function __construct(
        CountryRepositoryContract $countryRepository,
        AddressRepositoryContract $addressRepository,
        BasketRepositoryContract $basketRepo,
        AuthHelper $authHelper
    ) {
        $this->authHelper        = $authHelper;
        $this->addressRepo       = $addressRepository;
        $this->basketRepo        = $basketRepo;
        $this->countryRepository = $countryRepository;
    }
    
    /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array
     *
     * @return Address[]
     */
    public function getCustomerAddressData(): array
    {
        $basket = $this->getBasket();
        $addresses = array();
        $invoiceAddressId = $basket->customerInvoiceAddressId;
        $addresses['billing'] = empty($invoiceAddressId) ? null : $this->getAddressById($invoiceAddressId);
        // if the shipping address is -99 or null, it is matching the billing address.
        $shippingAddressId = $basket->customerShippingAddressId;
        if (empty($shippingAddressId) || $shippingAddressId === -99) {
            $addresses['shipping'] = $addresses['billing'];
        } else {
            $addresses['shipping'] = $this->getAddressById($shippingAddressId);
        }
        return $addresses;
    }

    /**
     * Returns true if the billing address is B2B
     *
     * @return bool
     */
    public function isBasketB2B(): bool
    {
        $billingAddress = $this->getCustomerAddressData()['billing'];

        return $billingAddress ? $billingAddress->gender === null : false;
    }

    /**
     * Fetches current basket and returns it
     *
     * @return Basket
     */
    public function getBasket(): Basket
    {
        return $this->basketRepo->load();
    }

    /**
     * Get country ISO2 code
     *
     * @param int $countryId  Plenty Country ID
     *
     * @return string
     */
    public function getCountryCode(int $countryId): string
    {
        return $countryId ? $this->countryRepository->findIsoCode($countryId, 'isoCode2') : '';
    }

    /**
     * Get country state name
     *
     * @param int $countryId  Plenty Country ID
     * @param int $stateId  Plenty State ID
     *
     * @return string
     */
    public function getCountryState(int $countryId, int $stateId): string
    {
        if (empty($stateId)) {
            return '';
        }
        $country = $this->countryRepository->getCountryById($countryId);
        $state = $country->states->where('id', '=', $stateId)->first();

        return $state->name;
    }

    /**
     * Return Address
     *
     * @param $addressId
     *
     * @return Address|null
     */
    private function getAddressById(int $addressId)
    {
        return $this->authHelper->processUnguarded(
            function () use ($addressId) {
                return $this->addressRepo->findAddressById($addressId);
            }
        );
    }
}
