<?php
namespace HeidelpayMGW\Controllers;

use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Helpers\PaymentHelper;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\PluginSettingRepository;
use HeidelpayMGW\Repositories\InvoiceInformationRepository;
use HeidelpayMGW\Repositories\PaymentInformationRepository;

class WebhooksController extends Controller
{
    use Loggable;
    
    private $settings;
    private $libContract;
    private $paymentInformationRepo;
    private $paymentHelper;

    public function __construct(
        PluginSettingRepository $pluginSettingsRepo,
        LibraryCallContract $libContract,
        PaymentInformationRepository $paymentInformationRepo,
        PaymentHelper $paymentHelper
    ) {
        $this->settings = $pluginSettingsRepo->get();
        $this->libContract = $libContract;
        $this->paymentInformationRepo = $paymentInformationRepo;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Listen for incomming webhooks
     *
     * @param Response  $response
     * @param Request   $request
     *
     * @return string
     */
    public function handleWebhook(Response $response, Request $request)
    {
        $libResponse = $this->libContract->call(PluginConfiguration::PLUGIN_NAME.'::webhookResource', [
            'privateKey' => $this->settings->privateKey,
            'jsonRequest' => $request->getContent(),
        ]);
        $hook = json_decode($request->getContent(), true);
        if (!$this->paymentHelper->handleWebhook($hook, $libResponse)) {
            return $response->forceStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //FOR DEBUGING
        $this->getLogger(__METHOD__)->error(
            'webhook '.$hook['event'],
            [
                'hook' => $hook,
                'libResponse' => $libResponse,
            ]
        );

        return $response->forceStatus(Response::HTTP_OK);
    }
}
