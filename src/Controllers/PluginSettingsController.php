<?php
namespace Heidelpay\Controllers;

use Plenty\Modules\System\Contracts\WebstoreConfigurationRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Configuration\PluginConfiguration;
use Heidelpay\Repositories\PluginSettingRepository;

class PluginSettingsController extends Controller
{
    use Loggable;

    private $pluginSettingRepository;

    public function __construct(
        PluginSettingRepository $pluginSettingRepository
    ) {
        $this->pluginSettingRepository = $pluginSettingRepository;
    }

    /**
     * Get settings from DB
     *
     * @param Response  $response
     * @param Request   $request
     *
     * @return string
     */
    public function getSettings(Response $response, Request $request)
    {
        try {
            $pluginSetting = $this->pluginSettingRepository->get();

            return $response->json([
                'success' => true,
                'pluginSetting' => $pluginSetting
            ]);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'error' => $e->getMessage()
                ]
            );

            return $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save settings to DB
     *
     * @param Response $response
     * @param Request  $request
     *
     * @return string
     */
    public function saveSettings(Response $response, Request $request, LibraryCallContract $lib, WebstoreConfigurationRepositoryContract $webstoreConfig)
    {
        try {
            $settings = $this->pluginSettingRepository->save($request->except('plentymarkets'));
            $webstore = $webstoreConfig->findByPlentyId(pluginApp(Application::class)->getPlentyId());
            $libResponse = $lib->call(PluginConfiguration::PLUGIN_NAME.'::registerWebhook', [
                'privateKey' => $settings->privateKey,
                'baseUrl' => ($webstore->domainSsl ?? $webstore->domain),
                'routeName' => PluginConfiguration::PLUGIN_NAME,
            ]);
            if (!$libResponse['success']) {
                $this->getLogger(__METHOD__)->error(
                    'Webhook register error',
                    [
                        'error' => $libResponse
                    ]
                );
            }

            return $response->json([
                'success' => true,
                'pluginSetting' => $settings
            ]);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'error' => $e->getMessage()
                ]
            );

            return $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
