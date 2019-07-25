<?php
namespace Heidelpay\Controllers;

use Plenty\Modules\Order\ReturnReason\Contracts\ReturnReasonRepositoryContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use Heidelpay\Helpers\Loggable;
use Heidelpay\Configuration\PluginConfiguration;
use Heidelpay\Repositories\InvoiceGuaranteedSettingRepository;

class InvoiceGuaranteedSettingsController extends Controller
{
    use Loggable;

    private $invoiceGuaranteedSettingRepository;

    public function __construct(
        InvoiceGuaranteedSettingRepository $invoiceGuaranteedSettingRepository
    ) {
        $this->invoiceGuaranteedSettingRepository = $invoiceGuaranteedSettingRepository;
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
            $settings = $this->invoiceGuaranteedSettingRepository->get();

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
                'settings' => $this->invoiceGuaranteedSettingRepository->save($request->except('plentymarkets'))
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
