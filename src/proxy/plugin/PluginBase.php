<?php namespace proxy\plugin;

use pocketmine\network\mcpe\protocol\DataPacket;
use proxy\hosts\Client;
use proxy\hosts\ProxyClient;
use proxy\ProxyServer;

abstract class PluginBase implements PluginInterface {

    /**
     * @var ProxyServer $proxyServer
     * Don't override this! @kaliiks, it is not possible when it is private :D ...
     */
    private $proxyServer;

    /**
     * @var PluginDescription $description
     */
    private $description;

    /**
     * @var bool $initialized
     */
    private $initialized = false;

    /**
     * @var bool $isEnabled
     */
    private $isEnabled = true;

    public function onEnable() : void{

    }

    public function onDisable() : void{

    }

    public function handlePacketReceive(DataPacket $packet): void
    {

    }

    public function handlePacketSend(DataPacket $packet): void
    {

    }

    public function onCommand(ProxyClient $client, string $command, array $args) : void
    {

    }


    /**
     * @return bool
     */
    public function isEnabled() : bool{
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     * @param bool $callDisable
     */
    public function setEnabled(bool $isEnabled, $callDisable = true) : void{
        $this->isEnabled = $isEnabled;
        if($callDisable)$this->onDisable();
    }

    /**
     * @return PluginDescription
     */
    public function getDescription() : PluginDescription{
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isInitialized() : bool{
        return $this->initialized;
    }


    /**
     * @param ProxyServer $proxyServer
     * @param PluginDescription $description
     */
    public function init(ProxyServer $proxyServer, PluginDescription $description){
        $this->proxyServer = $proxyServer;
        $this->description = $description;
        $this->initialized = true;
        $this->onEnable();
    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }

    /**
     * @return ProxyClient
     */
    public function getClient() : ProxyClient{
        return $this->getProxy()->getClient();
    }

}


