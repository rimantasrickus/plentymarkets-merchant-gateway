<?php
namespace HeidelpayMGW\Helpers;

use HeidelpayMGW\Configuration\PluginConfiguration;
use Plenty\Plugin\Log\Loggable as PlentyLogger;
use Plenty\Plugin\Application;

trait Loggable
{
    public function getLogger($identifier)
    {
        return pluginApp(Logger::class, [$identifier]);
    }
}

class Logger
{
    use PlentyLogger;
   
    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function debug($message, $value)
    {
        $this->getLogger($this->identifier)->debug(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    public function info($message, $value)
    {
        $this->getLogger($this->identifier)->info(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    public function error($message, $value)
    {
        $this->getLogger($this->identifier)->error($message, $value);
    }

    public function notice($message, $value)
    {
        $this->getLogger($this->identifier)->notice(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    public function warning($message, $value)
    {
        $this->getLogger($this->identifier)->warning(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
    }

    public function exception($message, $value)
    {
        $this->getLogger($this->identifier)->error(PluginConfiguration::PLUGIN_NAME.'::'.$message, $value);
        $this->sendMessageToSlack($this->identifier, $value);
    }


    private function sendMessageToSlack($identifier, $value)
    {
        $plentyId =$this->getPlentyId();
        $messageHeader = "*". $plentyId ." | ". PluginConfiguration::PLUGIN_NAME." | " .PluginConfiguration::PLUGIN_VERSION. "*" . "`$identifier`";
        
        $attachments = [
            "text" => "```" . json_encode($value) . "````",
            "color" => 'danger',
            "attachment_type"=> "default"
        
         ];

        $headers = [
            "Authorization: Bearer " . PluginConfiguration::SLACK_API_TOKEN,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, PluginConfiguration::SLACK_API_URL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "channel" => PluginConfiguration::SLACK_CHANNEL_ID,
            "text" => $messageHeader,
            "attachments" => array($attachments)
        ]));
        curl_exec($curl);
        curl_close($curl);
    }

    /**
    * Get PlentyId from configuration
    *
    * @return int
    */
    private function getPlentyId()
    {
        return pluginApp(Application::class)->getPlentyId();
    }
}
