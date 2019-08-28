<?php

namespace HeidelpayMGW\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\SessionHelper;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
* Saves payment data created in checkout to plugin's session
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
class PaymentTypeController extends Controller
{
    use Loggable;

    public function __construct()
    {
    }

    /**
     * Set session value to payment data from Frontend
     *
     * @param Response  $response
     * @param Request   $request
     * @param SessionHelper   $sessionHelper  Class for saving information to current session
     *
     * @return BaseResponse
     */
    public function heidelpayMGWPaymentType(Response $response, Request $request, SessionHelper $sessionHelper): BaseResponse
    {
        $frontendData = $request->except('plentyMarkets');
        $data = $frontendData['data'];
        $heidelpayBirthDate = $frontendData['heidelpayBirthDate'];

        $sessionHelper->setValue('paymentType', $data);
        $sessionHelper->setValue('heidelpayBirthDate', $heidelpayBirthDate);

        return $response->json([
            'success' => true
        ]);
    }
}
