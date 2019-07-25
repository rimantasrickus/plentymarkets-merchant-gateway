<?php
namespace Heidelpay\Methods;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\Application;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Services\BasketService;
use Heidelpay\Configuration\PluginConfiguration;
use Heidelpay\Repositories\InvoiceSettingRepository;

class InvoicePaymentMethod extends PaymentMethodService
{
    use Loggable;

    const RESTRICTED_COUNTRIES = ['DE', 'AU'];

    private $settings;
    private $basketService;

    public function __construct(
        InvoiceSettingRepository $invoiceSettingRepository,
        BasketService $basketService
    ) {
        $this->settings = $invoiceSettingRepository->get();
        $this->basketService = $basketService;
    }

    /**
     * Check whether the plugin is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->settings->isActive) {
            return false;
        }

        $basket = $this->basketService->getBasket();
        if ($this->basketService->isBasketB2B()) {
            return false;
        }
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
     * Get the name of the plugin.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->settings->displayName;
    }

    /**
     * Get additional costs for Crefopay.
     *
     * @return float
     */
    public function getFee()
    {
        return 0.00;
    }

    /**
     * Get the path of the icon.
     *
     * @return string
     */
    public function getIcon(): string
    {
        $app = pluginApp(Application::class);
        
        return $this->settings->iconURL ?: $app->getUrlPath(PluginConfiguration::PLUGIN_NAME) . '/images/default_payment_icon.png';
    }

    /**
     * Get the description of the payment method.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return PluginConfiguration::INVOICE_FRONTEND_NAME;
    }

    /**
     * Check country of the address
     *
     * @return bool
     */
    private function isCountryRestricted(): bool
    {
        $address = $this->basketService->getCustomerAddressData()['billing'];
        if (in_array($address->country->isoCode2, self::RESTRICTED_COUNTRIES)) {
            return false;
        }

        return true;
    }
}
