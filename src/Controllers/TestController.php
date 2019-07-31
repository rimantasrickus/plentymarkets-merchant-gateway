<?php
namespace HeidelpayMGW\Controllers;

use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Controller;

use HeidelpayMGW\Helpers\Loggable;
use HeidelpayMGW\Configuration\PluginConfiguration;

class TestController extends Controller
{
    use Loggable;

    public function __construct()
    {
    }

    /**
     * Reset model.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return string
     */
    public function reset(Request $request, Response $response, Migrate $migrate)
    {
        $model = $request->get('model');
        $migrate->deleteTable("HeidelpayMGW\\Models\\".$model);
        $migrate->createTable("HeidelpayMGW\\Models\\".$model);

        return $response->json("Ok");
    }

    /**
     * Update model.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return string
     */
    public function update(Request $request, Response $response, Migrate $migrate)
    {
        $model = $request->get('model');
        $migrate->updateTable("HeidelpayMGW\\Models\\".$model);

        return $response->json("Ok");
    }

    public function show(Request $request, Response $response)
    {
        return $response->json(pluginApp(\Plenty\Modules\Plugin\DataBase\Contracts\DataBase::class)->query("HeidelpayMGW\\Models\\".$request->get('model'))->get());
    }


    public function cron(Request $request, Response $response)
    {
        switch ($request->get('model')) {
            default:
                return $response->json('no model provided');
                break;
        }
    }

    public function lib(Request $request, Response $response)
    {
        $settingsRepo = pluginApp(\HeidelpayMGW\Repositories\PluginSettingRepository::class);
        $settings = $settingsRepo->get();

        $lib = pluginApp(LibraryCallContract::class);
        $libResponse = $lib->call(PluginConfiguration::PLUGIN_NAME.'::invoice', [
            'publicKey' => $settings->publicKey,
            'privateKey' => $settings->privateKey,
            'returnUrl' => 'http://test.com/',
        ]);

        return $response->json($libResponse);
    }
}
