<?php

declare(strict_types=1);

namespace acidproxy\network;

use acidproxy\utils\InternetAddress;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use acidproxy\plugin\PluginBase;
use acidproxy\ProxyServer;

/**
 * Class UpstreamAbstractConnection
 * @package acidproxy\network
 */
class UpstreamAbstractConnection extends AbstractConnection {

    /** @var InternetAddress $address */
    public $address;

    /** @var ProxyServer $server */
    private $server;

    /** @var DownstreamAbstractConnection $downstreamConnection */
    private $downstreamConnection;

    /** @var bool $connected */
    private $connected = false;

    /** @var string $username */
    private $username;

    /** @var string $uuid */
    private $uuid;

    /** @var string $xuid */
    private $xuid;

    /** @var int $entityRuntimeId */
    private $entityRuntimeId;

    /**
     * UpstreamAbstractConnection constructor.
     * @param InternetAddress $address
     * @param ProxyServer $server
     */
    public function __construct(InternetAddress $address, ProxyServer $server) {
        $this->address = $address;
        $this->server = $server;
    }

    /**
     * @param DataPacket $packet
     * @return void
     */
    public function handlePacket(DataPacket $packet): void {
        @$packet->decode();
        $packetId = $packet::NETWORK_ID;
        switch($packetId){
            case LoginPacket::NETWORK_ID;
                /** @var LoginPacket $packet */
                $this->getProxy()->getLogger()->info("Login version number: " . $packet->protocol);

                $this->username = $packet->username;
                $this->uuid = $packet->clientUUID;
                $this->xuid = $packet->xuid;

                if($packet->xuid !== ""){
                    $this->getProxy()->getLogger()->info("Got valid XBOX Live Account ID: " . $packet->xuid);
                }

                $this->getProxy()->getLogger()->info("Logged in as {$packet->username} UUID: {$packet->clientUUID}");
                break;
            case DisconnectPacket::NETWORK_ID;
                break;
        }
        foreach($this->server->getPluginManager()->getPlugins() as $plugin){
            if(!$plugin instanceof PluginBase)return;
            $plugin->handlePacketSend($packet);
        }
    }

    /**
     * @return void
     */
    public  function disconnect(): void {

    }

    /**
     * @param DataPacket $packet
     * @return void
     */
    public function send(DataPacket $packet): void {
        $this->server->getNetworkUtils()->writeDataPacket($packet, $this->server->downstreamConnection);
    }


    /**
     * @return bool
     */
    public function isConnected(): bool{
        return $this->connected;
    }

    /**
     * @param bool $connected
     * @return void
     */
    public function setConnected(bool $connected): void{
        $this->connected = $connected;
    }

    /**
     * @param int $entityRuntimeId
     * @return void
     */
    public function setId(int $entityRuntimeId): void{
        $this->entityRuntimeId = $entityRuntimeId;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy(): ProxyServer {
        return $this->server;
    }
}