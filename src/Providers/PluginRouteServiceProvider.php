<?php
namespace Heidelpay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\ApiRouter;
use Plenty\Plugin\Routing\Router;

use Heidelpay\Configuration\PluginConfiguration;

class PluginRouteServiceProvider extends RouteServiceProvider
{

    /**
     * @param Router $router
     */
    public function map(
        Router $router,
        ApiRouter $apiRouter
    ) {
        $apiRouter->version(
            ['v1'],
            ['namespace' => 'Heidelpay\Controllers', 'middleware' => 'oauth'],
            function ($apiRouter) {
                //Plugin settings
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/plugin-settings', 'PluginSettingsController@getSettings');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/plugin-settings', 'PluginSettingsController@saveSettings');
                //Invoice settings
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/invoice-settings', 'InvoiceSettingsController@getSettings');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/invoice-settings', 'InvoiceSettingsController@saveSettings');
                //Invoice guaranteed B2C settings
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/invoice-guaranteed-settings', 'InvoiceGuaranteedSettingsController@getSettings');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/invoice-guaranteed-settings', 'InvoiceGuaranteedSettingsController@saveSettings');
                //Invoice guaranteed B2B settings
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/invoice-guaranteedb2b-settings', 'InvoiceGuaranteedB2BSettingsController@getSettings');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/invoice-guaranteedb2b-settings', 'InvoiceGuaranteedB2BSettingsController@saveSettings');

                //Test
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/show', 'TestController@show');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/reset', 'TestController@reset');
                $apiRouter->post(PluginConfiguration::PLUGIN_NAME.'/update', 'TestController@update');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/cron', 'TestController@cron');
                $apiRouter->get(PluginConfiguration::PLUGIN_NAME.'/lib', 'TestController@lib');
            }
        );

        $router->post(PluginConfiguration::PLUGIN_NAME.'/payment-type', 'Heidelpay\Controllers\PaymetTypeController@HeidelpayPaymetType');
        $router->post(PluginConfiguration::PLUGIN_NAME.'/webhooks', 'Heidelpay\Controllers\WebhooksController@handleWebhook');
    }
}
