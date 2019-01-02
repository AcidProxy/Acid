<?php

declare(strict_types=1);

namespace acidproxy\network;

use acidproxy\utils\InternetAddress;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use acidproxy\plugin\PluginBase;
use acidproxy\ProxyServer;

/**
 * Class DownstreamAbstractConnection
 * @package acidproxy\network
 */
class DownstreamAbstractConnection extends AbstractConnection {

    /** @var InternetAddress $address */
    public $address;

    /** @var ProxyServer $server */
    private $server;

    /** @var UpstreamAbstractConnection $upstreamConnection */
    private $upstreamConnection;

    /** @var array $spawnedEntities */
    private $spawnedEntities = [];

    /**
     * DownstreamAbstractConnection constructor.
     * @param InternetAddress $address
     * @param ProxyServer $server
     * @param UpstreamAbstractConnection $upstreamConnection
     */
    public function __construct(InternetAddress $address, ProxyServer $server, UpstreamAbstractConnection $upstreamConnection) {
        $this->address = $address;
        $this->server = $server;
        $this->upstreamConnection = $upstreamConnection;
    }

    /**
     * @param DataPacket $packet
     */
    public function handlePacket(DataPacket $packet): void {
        $packetId = $packet::NETWORK_ID;
        switch($packetId){
            case StartGamePacket::NETWORK_ID;
                /**
                 * @var StartGamePacket $packet
                 */
                $packet->decode();
                $this->upstreamConnection->setId($packet->entityRuntimeId);
                $this->server->getClient()->handleStartGame($packet);
                break;
            case ServerToClientHandshakePacket::NETWORK_ID;
                /**
                 * @var ServerToClientHandshakePacket $packet
                 */

                //todo: send response
               break;
            case AddEntityPacket::NETWORK_ID;
            case AddPlayerPacket::NETWORK_ID;
                /**
                 * @var AddEntityPacket $packet
                 * @var AddPlayerPacket $packet
                 * @var AddItemEntityPacket $packet
                 */
             //   $packet->decode();

                array_push($this->spawnedEntities, $packet->entityUniqueId);
                break;
            case RemoveEntityPacket::NETWORK_ID;
                /**
                 * @var RemoveEntityPacket $packet
                 */
                $packet->decode();

                if(in_array($packet->entityUniqueId, $this->spawnedEntities)){
                    unset($this->spawnedEntities[array_search($packet->entityUniqueId, $this->spawnedEntities)]);
                }
                break;
            case DisconnectPacket::NETWORK_ID;
                /**
                 * @var DisconnectPacket $packet
                 */
                $this->upstreamConnection->setConnected(false);
                break;
            case TransferPacket::NETWORK_ID;
                /**
                 * @var TransferPacket $packet
                 */
                  $packet->decode();

                  $transfer = new TransferPacket();
                  $transfer->address = $packet->address;
                  $transfer->port = $packet->port;
                  $transfer->encode();

                break;
        }
        foreach($this->server->getPluginManager()->getPlugins() as $plugin){
            if(!$plugin instanceof PluginBase)return;
            $plugin->handlePacketReceive($packet);
        }
    }

    /**
     * @return void
     */
    public function disconnect(): void {}

    /**
     * @param DataPacket $packet
     * @return void
     */
    public function send(DataPacket $packet): void {
        $this->server->getNetworkUtils()->writeDataPacket($packet, $this->upstreamConnection);
    }

}