<?php namespace proxy\plugin;


use pocketmine\utils\TextFormat;
use proxy\utils\Logger;

class PluginDescription
{

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string $apiVersion
     */
    private $apiVersion;

    /**
     * @var string $version
     */
    private $version;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var string $author
     */
    private $author;

    private $commands = [];

    /**
     * PluginDescription constructor.
     * @param array $pluginData
     * @param Logger $logger
     * @throws \Exception
     */
    public function __construct(array $pluginData, Logger $logger) {
        $this->name = $pluginData['name'];
        if(preg_match('/^[A-Za-z0-9 _.-]+$/', $this->name) === 0){
            throw new \Exception("Invalid PluginDescription name");
        }

        $this->name = str_replace(" ", "_", $this->name);
        $this->version = $pluginData['version'];
        $this->apiVersion = $pluginData['api'];
        $this->description = $pluginData['description'];

        if(isset($pluginData['commands']) && is_array($pluginData['commands'])){
            $this->commands = $pluginData['commands'];
        }
    }

    /**
     * @return array
     */
    public function getCommands() : array{
        return $this->commands;
    }

    /**
     * @return string
     */
    public function getDescription() : string{
        return $this->description;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

}