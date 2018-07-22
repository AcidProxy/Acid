<?php

namespace proxy\plugin;


use pocketmine\utils\TextFormat;
use proxy\ProxyServer;

class PluginManager
{

    /**
     * @var PluginInterface[] $plugins
     */
    private $plugins = [];

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * @var string $pluginDirectory
     */
    private $pluginDirectory;

    /**
     * PluginManager constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
        $this->proxyServer = $proxyServer;
    }

    /**
     * @return string
     */
    public function getPluginDirectory() : string{
        return $this->pluginDirectory;
    }

    /**
     * @return array
     */
    public function getPlugins() : array{
        return $this->plugins;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function disablePlugin(string $name) : bool{
         if(!isset($this->plugins[$name])){
             $this->getProxy()->getLogger()->error("Failed to unload plugin: Plugin does not exist");
             return false;
         }
         $this->plugins[$name]->setEnabled(false);
         $this->getProxy()->getLogger()->info(TextFormat::GREEN . "Disabled plugin " .$name);
         return true;
    }

    /**
     * @param string $name
     *
     * This can be executed from plugins only
     */
    public function loadPlugin(string $name) : void{
        if(isset($this->plugins[$name])){
            if($this->plugins[$name]->isEnabled()){
                $this->getProxy()->getLogger()->error("Failed to load plugin: Plugin with same name is already loaded");
            }else{
                $this->plugins[$name]->setEnabled(true);
                $this->getProxy()->getLogger()->info(TextFormat::GREEN . "Loaded plugin " . $name);
            }
            return;
        }else{
            $this->getProxy()->getLogger()->error("Failed to load plugin: Plugin does not exist");
        }
    }


    /**
     * @param string $directory
     * @throws \Exception
     */
    public function loadPlugins(string $directory) : void{
        @mkdir($directory);
        $this->pluginDirectory = $directory;
        $this->getProxy()->getLogger()->info(TextFormat::GREEN . "Loading plugins...");
        $pl = glob($directory . "/*");
        foreach($pl as $dir){
            if(!is_file($directory) && file_exists($plugin = $dir . "/plugin.yml")){
                $configData = yaml_parse_file($plugin);
                if(!empty($configData['name'])){
                if(isset($configData['api']) && strval($configData['api']) === ProxyServer::SERVER_API){
                        $this->getProxy()->getLogger()->info(TextFormat::YELLOW . "Loading " . $configData['name'] . "...");
                        if(file_exists($main = dirname($plugin) . "/src/" . str_replace("\\", "/", $configData["main"]) . ".php")){
                            /** @noinspection PhpIncludeInspection */
                             require_once($main);
                             $plugin = new $configData['main'];
                             if(is_a($plugin, PluginBase::class)){
                                 $this->plugins[$configData['name']] = $plugin;
                                 $plugin->init($this->getProxy(), new PluginDescription($configData, $this->getProxy()->getLogger()));
                             }else{
                                 $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Main class must extend PluginBase");
                             }

                        }else{
                            $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Main class not found");
                        }
                    }else {
                    $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Incomaptible API version");
                   }
                }
            }
        }
    }

}