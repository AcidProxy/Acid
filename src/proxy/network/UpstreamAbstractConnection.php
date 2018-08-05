<?php

namespace proxy\network;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use proxy\plugin\PluginBase;
use proxy\ProxyServer;
use proxy\utils\Logger;
use raklib\utils\InternetAddress;

class UpstreamAbstractConnection extends AbstractConnection
{

    /**
     * @var InternetAddress $address
     */
    public $address;

    /**
     * @var ProxyServer $server
     */
    private $server;

    /**
     * @var DownstreamAbstractConnection $downstreamConnection
     */
    private $downstreamConnection;

    /**
     * @var bool $connected
     */
    private $connected = false;

    /**
     * @var string $username
     */
    private $username;

    /**
     * @var string $uuid
     */
    private $uuid;

    /**
     * @var string $xuid
     */
    private $xuid;

    /**
     * @var int $entityRuntimeId
     */
    private $entityRuntimeId;

    /**
     * UpstreamAbstractConnection constructor.
     * @param InternetAddress $address
     * @param ProxyServer $server
     */
    public function __construct(InternetAddress $address, ProxyServer $server)
    {
        $this->address = $address;
        $this->server = $server;
    }

    /**
     * @param DataPacket $packet
     * @return void
     */
    public function handlePacket(DataPacket $packet): void
    {
        @$packet->decode();
        $packetId = $packet::NETWORK_ID;
        switch($packetId){
            case LoginPacket::NETWORK_ID;
                /**
                 * @var LoginPacket $packet
                 */
                Logger::log("Login version number: " . $packet->protocol);

                $this->username = $packet->username;
                $this->uuid = $packet->clientUUID;
                $this->xuid = $packet->xuid;

                if($packet->xuid !== ""){
                    Logger::log("Got valid XBOX Live Account ID: " . $packet->xuid);
                }

                Logger::log("Logged in as {$packet->username} UUID: {$packet->clientUUID} ");
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
    public  function disconnect(): void
    {

    }

    /**
     * @param DataPacket $packet
     * @return void
     */
    public function send(DataPacket $packet): void
    {
        $this->server->getNetworkUtils()->writeDataPacket($packet, $this->server->downstreamConnection);
    }


    /**
     * @return bool
     */
    public function isConnected() : bool{
        return $this->connected;
    }

    /**
     * @param bool $connected
     * @return void
     */
    public function setConnected(bool $connected) : void{
        $this->connected = $connected;
    }

    /**
     * @param int $entityRuntimeId
     * @return void
     */
    public function setId(int $entityRuntimeId) : void{
        $this->entityRuntimeId = $entityRuntimeId;
    }
}