<?php

namespace HeidelpayMGW\Services;

use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Item\Item\Models\Item;
use Plenty\Modules\Basket\Models\Basket;

use HeidelpayMGW\Helpers\Loggable;

class BasketService
{
    use Loggable;

    private $authHelper;
    private $itemRepo;
    private $addressRepo;
    private $basketRepo;
    private $countryRepository;

    /**
     * BasketService constructor.
     *
     * @param CountryRepositoryContract $countryRepository
     * @param AddressRepositoryContract $addressRepository
     * @param BasketRepositoryContract $basketRepo
     * @param LibService $libraryService
     * @param ItemRepositoryContract $itemRepo
     * @param AuthHelper $authHelper
     */
    public function __construct(
        CountryRepositoryContract $countryRepository,
        AddressRepositoryContract $addressRepository,
        BasketRepositoryContract $basketRepo,
        ItemRepositoryContract $itemRepo,
        AuthHelper $authHelper
    ) {
        $this->authHelper          = $authHelper;
        $this->itemRepo            = $itemRepo;
        $this->addressRepo         = $addressRepository;
        $this->basketRepo          = $basketRepo;
        $this->countryRepository   = $countryRepository;
    }
    
    /**
     * Gathers address data (billing/invoice and shipping) and returns them as an array.
     *
     * @return Address[]
     */
    public function getCustomerAddressData(): array
    {
        $basket = $this->getBasket();
        $addresses            = [];
        $invoiceAddressId     = $basket->customerInvoiceAddressId;
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
     * Returns true if the billing address is B2B.
     *
     * @return bool
     */
    public function isBasketB2B(): bool
    {
        $billingAddress = $this->getCustomerAddressData()['billing'];

        return $billingAddress ? $billingAddress->gender === null : false;
    }

    /**
     * Fetches current basket and returns it.
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
     * @param int $countryId
     *
     * @return string
     */
    public function getCountryCode(int $countryId): string
    {
        return $countryId ? $this->countryRepository->findIsoCode($countryId, 'isoCode2') : '';
    }

    /**
     * Get country state
     *
     * @param int $countryId
     * @param int $stateId
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
        $authHelper = pluginApp(AuthHelper::class);
        $address = $authHelper->processUnguarded(
            function () use ($addressId) {
                return $this->addressRepo->findAddressById($addressId);
            }
        );
        return $address;
    }
}
