<?php
namespace Heidelpay\Helpers;

use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;

use Heidelpay\Configuration\PluginConfiguration;

class SessionHelper
{
    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $sessionStorage;
    
    public function __construct(FrontendSessionStorageFactoryContract $sessionStorage)
    {
        $this->sessionStorage = $sessionStorage;
    }
    
    /**
     * Set the session value
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue(string $name, $value)
    {
        $this->sessionStorage->getPlugin()->setValue(PluginConfiguration::PLUGIN_NAME.'_'.$name, $value);
    }
    
    /**
     * Get the session value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getValue(string $name)
    {
        return $this->sessionStorage->getPlugin()->getValue(PluginConfiguration::PLUGIN_NAME.'_'.$name);
    }
}
