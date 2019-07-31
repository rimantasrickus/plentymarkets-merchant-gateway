<?php
namespace HeidelpayMGW\Controllers;

use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\SessionHelper;
use HeidelpayMGW\Configuration\PluginConfiguration;

class PaymetTypeController extends Controller
{
    use Loggable;

    private $sessionHelper;

    public function __construct(SessionHelper $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * Set session value to payment from Frontend
     *
     * @param Response  $response
     * @param Request   $request
     *
     * @return string
     */
    public function HeidelpayMGWPaymetType(Response $response, Request $request)
    {
        $this->sessionHelper->setValue('paymentType', $request->except('plentyMarkets'));

        return $response->json([
            'success' => true
        ]);
    }
}
