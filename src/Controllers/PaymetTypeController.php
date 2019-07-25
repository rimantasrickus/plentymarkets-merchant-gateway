<?php
namespace Heidelpay\Controllers;

use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Helpers\SessionHelper;
use Heidelpay\Configuration\PluginConfiguration;

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
    public function HeidelpayPaymetType(Response $response, Request $request)
    {
        $this->sessionHelper->setValue('paymentType', $request->except('plentyMarkets'));

        return $response->json([
            'success' => true
        ]);
    }
}
