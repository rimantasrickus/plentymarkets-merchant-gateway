<?php
namespace HeidelpayMGW\Controllers;

use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\InvoiceSettingRepository;

class InvoiceSettingsController extends Controller
{
    use Loggable;

    private $invoiceSettingRepository;

    public function __construct(
        InvoiceSettingRepository $invoiceSettingRepository
    ) {
        $this->invoiceSettingRepository = $invoiceSettingRepository;
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
            $settings = $this->invoiceSettingRepository->get();

            return $response->json([
                'success' => true,
                'settings' => $settings
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
    public function saveSettings(Response $response, Request $request)
    {
        try {
            return $response->json([
                'success' => true,
                'settings' => $this->invoiceSettingRepository->save($request->except('plentymarkets'))
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
