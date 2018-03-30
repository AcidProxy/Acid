<?php

namespace proxy\command;


use pocketmine\utils\TextFormat;
use proxy\plugin\PluginManager;
use proxy\ProxyServer;
use proxy\command\Command;

class ProxyCommandMap
{

    /**
     * @var Command[] $commands
     */
    private $commands = [];

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * ProxyCommandMap constructor.
     * @param PluginManager $pluginManager
     * @param ProxyServer $proxyServer
     */
    public function __construct(PluginManager $pluginManager, ProxyServer $proxyServer)
    {
        $this->proxyServer = $proxyServer;
        foreach($pluginManager->getPlugins() as $plugin){
            foreach($plugin->getDescription()->getCommands() as $command => $data){
                $this->commands[$command] = new PluginCommand($command, $data['usage'], $plugin);
                $proxyServer->getLogger()->info(TextFormat::GREEN . "Registred: " . $command);
            }
        }
    }

    /**
     * @return array
     */
    public function getCommands() : array{
        return $this->commands;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }

    /**
     * @param string $name
     * @return null|\proxy\command\Command
     */
    public function getCommand(string $name) : ?Command{
        if(empty($this->commands[$name])){
            return null;
        }
        return $this->commands[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function unregisterCommand(string $name) : bool{
        if(!isset($this->commands[$name])){
            $this->getProxy()->getLogger()->error("Tried to unregister non-existing command");
            return false;
        }
        unset($this->commands[$name]);
        return true;
    }

}