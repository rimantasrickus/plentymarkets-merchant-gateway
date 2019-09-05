<?php

namespace HeidelpayMGW\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\ApiKeysHelper;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

/**
* Handles webhooks coming from heidelpay
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
* @package  heidelpayMGW/controllers
*/
class WebhooksController extends Controller
{
    use Loggable;
    
    /** @var ApiKeysHelper $apiKeysHelper  Returns Api keys depending if it's sandbox or production mode */
    private $apiKeysHelper;

    /** @var LibraryCallContract $libContract */
    private $libContract;

    /** @var PaymentHelper $paymentHelper */
    private $paymentHelper;

    /**
     * WebhooksController constructor
     *
     * @param ApiKeysHelper $apiKeysHelper Plugin settings repository
     * @param LibraryCallContract $libContract
     * @param PaymentHelper $paymentHelper  Helper class to work with payment data
     */
    public function __construct(
        ApiKeysHelper $apiKeysHelper,
        LibraryCallContract $libContract,
        PaymentHelper $paymentHelper
    ) {
        $this->apiKeysHelper = $apiKeysHelper;
        $this->libContract = $libContract;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Listen for incoming webhooks
     *
     * @param Response  $response
     * @param Request   $request
     *
     * @return string
     */
    public function handleWebhook(Response $response, Request $request): Response
    {
        /** @var array $hook */
        $hook = json_decode($request->getContent(), true);
        
        $this->getLogger(__METHOD__)->debug(
            'translation.incomingWebhook',
            [
                'hook' => $hook,
            ]
        );

        if ($hook['publicKey'] !== $this->apiKeysHelper->getPublicKey()) {
            return $response->forceStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var array $libResponse */
        $libResponse = $this->libContract->call(PluginConfiguration::PLUGIN_NAME.'::webhookResource', [
            'privateKey' => $this->apiKeysHelper->getPrivateKey(),
            'jsonRequest' => $request->getContent()
        ]);

        try {
            if (!$this->paymentHelper->handleWebhook($hook, $libResponse)) {
                return $response->forceStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->exception(
                'translation.exception',
                [
                    'error' => $e->getMessage()
                ]
            );

            return $response->forceStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response->forceStatus(Response::HTTP_OK);
    }
}
