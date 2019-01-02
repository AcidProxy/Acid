<?php namespace acidproxy\plugin;

use pocketmine\network\mcpe\protocol\DataPacket;
use acidproxy\command\sender\Sender;
use acidproxy\hosts\ProxyClient;
use acidproxy\ProxyServer;

/**
 * Class PluginBase
 * @package proxy\plugin
 */
abstract class PluginBase implements PluginInterface
{

    /** @var ProxyServer $description */
    private $proxyServer;

    /** @var PluginDescription $description */
    private $description;

    /** @var bool $initialized */
    private $initialized = false;

    /** @var bool $isEnabled */
    private $isEnabled = true;

    /**
     * @return void
     */
    public function onEnable(): void{}

    /**
     * @return void
     */
    public function onDisable(): void{}

    /**
     * @param DataPacket $packet
     *
     * @return void
     */
    public function handlePacketReceive(DataPacket $packet): void{}

    /**
     * @param DataPacket $packet
     *
     * @return void
     */
    public function handlePacketSend(DataPacket $packet): void{}

    /**
     * @param Sender $client
     * @param string $command
     * @param array $args
     *
     * @return void
     */
    public function onCommand(Sender $client, string $command, array $args): void{}


    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     * @param bool $callDisable
     */
    public function setEnabled(bool $isEnabled, $callDisable = true): void
    {
        $this->isEnabled = $isEnabled;
        if ($callDisable) $this->onDisable();
    }

    /**
     * @return PluginDescription
     */
    public function getDescription(): PluginDescription
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }


    /**
     * @param ProxyServer $proxyServer
     * @param PluginDescription $description
     */
    public function init(ProxyServer $proxyServer, PluginDescription $description)
    {
        $this->proxyServer = $proxyServer;
        $this->description = $description;
        $this->initialized = true;
        $this->onEnable();
    }

    /**
     * @return ProxyServer
     */
    public function getServer(): ProxyServer
    {
        return $this->proxyServer;
    }

    /**
     * @return ProxyClient
     */
    public function getClient(): ProxyClient
    {
        return $this->getServer()->getClient();
    }
}


