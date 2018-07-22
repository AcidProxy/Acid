<?php

declare(strict_types=1);

namespace proxy\plugin;

use pocketmine\utils\TextFormat;
use proxy\command\base\PluginsCommand;
use proxy\command\PluginCommand;
use proxy\ProxyServer;

class PluginManager {

    /** @var array $plugins */
    private $plugins = [];

    /** @var ProxyServer $proxyServer */
    private $proxyServer;

    /** @var string $pluginDirectory */
    private $pluginDirectory;

    /**
     * PluginManager constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer) {
        $this->proxyServer = $proxyServer;
    }

    /**
     * @return string
     */
    public function getPluginDirectory(): string {
        return $this->pluginDirectory;
    }

    /**
     * @return array
     */
    public function getPlugins(): array {
        return $this->plugins;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy(): ProxyServer {
        return $this->proxyServer;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function disablePlugin(string $name): bool {
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
    public function loadPlugin(string $name): void {
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


    public function loadPlugins(string $directory) {
        if(!is_dir($directory)) {
            @mkdir($directory);
        }
        $this->pluginDirectory = $directory;

        $ds = DIRECTORY_SEPARATOR;
        $dirs = glob($directory . $ds . "*");
        $loaded = 0;

        foreach ($dirs as $dir) {
            if(!is_file($file = $dir. $ds . "plugin.yml")) {
                $this->getProxy()->getLogger()->error("Error while loading plugin " . basename($dir) . ": {$file} not found");
                break;
            }

            $data = yaml_parse_file($dataFile = $dir . $ds . "plugin.yml");
            if(!isset($data['name']) || !isset($data['api']) || !isset($data['version']) || !isset($data['description']) || !isset($data['main'])) {
                $this->getProxy()->getLogger()->error("Error while loading plugin " . basename($dir) . ": Invalid plugin description.");
                break;
            }

            if(!is_file($main = dirname($dataFile) . $ds . "src" . $ds . str_replace("\\", $ds, $data['main']) . ".php")) {
                $this->getProxy()->getLogger()->error("Error while loading plugin " . $data['name'] . ": Main class not found ($main).");
                break;
            }

            if($data['api'] !== ProxyServer::SERVER_API) {
                $this->getProxy()->getLogger()->error("Error while loading plugin " . $data['name'] . ": Incompatible api version.");
                break;
            }

            require $main;

            /** @var PluginBase $plugin */
            $plugin = new $data['main'];

            if(!is_a($plugin, PluginBase::class)) {
                $this->getProxy()->getLogger()->error("Could not load plugin " . $data['name'] . ": Invalid main class");
                break;
            }

            $this->plugins[$data['name']] = $plugin;

            if(isset($data['commands'])) {
                foreach ($data['commands'] as $commandName => $commandData) {
                    $description = null;
                    if(isset($data['commands'][$commandName]['description'])) {
                        $description = $data['commands'][$commandName]['description'];
                    }
                    $this->getProxy()->getCommandMap()->registerCommand(new PluginCommand($commandName, $description, $plugin));
                }
            }

            $plugin->init($this->getProxy(), new PluginDescription($data, $this->getProxy()->getLogger()));
            $loaded++;
        }

        $this->getProxy()->getLogger()->info("§a{$loaded} plugins loaded!");
    }

    /*
    public function loadPlugins(string $directory) : void {
        @mkdir($directory);
        $this->pluginDirectory = $directory;

        $this->getProxy()->getLogger()->info(TextFormat::GREEN . "Loading plugins...");

        $pl = glob($directory . "/*");
        foreach ($pl as $dir) {
            if (!is_file($directory) && file_exists($plugin = $dir . "/plugin.yml")) {
                $configData = yaml_parse_file($plugin);
                if (!empty($configData['name'])) {
                    if (isset($configData['api']) && strval($configData['api']) === ProxyServer::SERVER_API) {
                        $this->getProxy()->getLogger()->info(TextFormat::YELLOW . "Loading " . $configData['name'] . "...");
                        if (file_exists($main = dirname($plugin) . "/src/" . str_replace("\\", "/", $configData["main"]) . ".php")) {
                            /** @noinspection PhpIncludeInspection *./
                            require_once($main);
                            /** @var PluginBase $plugin *./
                            $plugin = new $configData['main'];
                            if (is_a($plugin, PluginBase::class)) {
                                $this->plugins[$configData['name']] = $plugin;
                                if(isset($configData['commands'])) {
                                    foreach ($configData['commands'] as $command => $commandData) {
                                        $description = null;
                                        if(isset($configData['commands'][$command]['description'])) {
                                            $description = $configData['commands'][$command]['description'];
                                        }
                                        $this->getProxy()->getCommandMap()->registerCommand(new PluginCommand($command, $description, $plugin));
                                    }
                                }
                                $plugin->init($this->getProxy(), new PluginDescription($configData, $this->getProxy()->getLogger()));
                            } else {
                                $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Main class must extend PluginBase");
                            }

                        } else {
                            $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Main class not found");
                        }
                    } else {
                        $this->getProxy()->getLogger()->error("Could not load plugin " . $configData['name'] . ": Incompatible API version");
                    }
                }
            }
        }
    }*/

}