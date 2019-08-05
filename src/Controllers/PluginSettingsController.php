<?php

namespace HeidelpayMGW\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Application;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\PluginSettingRepository;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Plenty\Modules\System\Contracts\WebstoreConfigurationRepositoryContract;

/**
 * Invoice settings controller for UI settings
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
 * @package  heidelpayMGW/controllers
 *
 * @author Rimantas <development@heidelpay.com>
 */
class PluginSettingsController extends Controller
{
    use Loggable;

    /** @var PluginSettingRepository $settingRepository */
    private $settingRepository;

    /** @var Response $response */
    private $response;

    /** @var Request $request */
    private $request;

    /**
     * PluginSettingsController constructor
     *
     * @param PluginSettingRepository $settingRepository  Repository class from which we get settings
     * @param Response $response
     * @param Request $request
     */
    public function __construct(PluginSettingRepository $settingRepository, Response $response, Request $request)
    {
        $this->settingRepository = $settingRepository;
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * Get settings from DB
     *
     * @return BaseResponse
     */
    public function getSettings(): BaseResponse
    {
        try {
            return $this->response->json([
                'success' => true,
                'settings' => $this->settingRepository->get()
            ]);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'error' => $e->getMessage()
                ]
            );

            return $this->response->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get settings from DB
     *
     * @return BaseResponse
     */
    public function saveSettings(): BaseResponse
    {
        try {
            /** @var LibraryCallContract $lib */
            $lib = pluginApp(LibraryCallContract::class);
            /** @var WebstoreConfigurationRepositoryContract $webstoreConfig */
            $webstoreConfig = pluginApp(WebstoreConfigurationRepositoryContract::class);

            $settings = $this->settingRepository->save($this->request->except('plentymarkets'));
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

                return $this->response->json([
                    'success' => false,
                    'message' => $libResponse['merchantMessage']
                ]);
            }

            return $this->response->json([
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

            return $this->response->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
