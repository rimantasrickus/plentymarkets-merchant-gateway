<?php
namespace HeidelpayMGW\Controllers;

use Plenty\Modules\Order\ReturnReason\Contracts\ReturnReasonRepositoryContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Configuration\PluginConfiguration;
use HeidelpayMGW\Repositories\InvoiceGuaranteedB2BSettingRepository;

class InvoiceGuaranteedB2BSettingsController extends Controller
{
    use Loggable;

    private $invoiceGuaranteedB2BSettingRepository;

    public function __construct(
        InvoiceGuaranteedB2BSettingRepository $invoiceGuaranteedB2BSettingRepository
    ) {
        $this->invoiceGuaranteedB2BSettingRepository = $invoiceGuaranteedB2BSettingRepository;
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
            $settings = $this->invoiceGuaranteedB2BSettingRepository->get();

            return $response->json([
                'success' => true,
                'settings' => $settings,
                'returnReasonList' => pluginApp(ReturnReasonRepositoryContract::class)->all()
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
                'settings' => $this->invoiceGuaranteedB2BSettingRepository->save($request->except('plentymarkets'))
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
