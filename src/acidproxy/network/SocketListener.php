<?php

declare(strict_types=1);

namespace acidproxy\network;

use acidproxy\ServerManager;
use acidproxy\utils\InternetAddress;
use pocketmine\utils\TextFormat;
use acidproxy\ProxyServer;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;

/**
 * Class SocketListener
 * @package acidproxy\network
 */
class SocketListener {

    /** @var ProxyServer $server */
    private $server;

    /** @var InternetAddress $clientAddress */
    private $clientAddress;

    /**
     * SocketListener constructor.
     * @param ProxyServer $server
     */
    public function __construct(ProxyServer $server) {
        $this->server = $server;
    }

    /**
     * @param string $buffer
     * @param InternetAddress $address
     */
    public function listen(string $buffer, InternetAddress $address): void {
        switch (ord($buffer{0})) {
            case UnconnectedPing::$ID;
                if (!$this->server->upstreamConnection->isConnected()) {
                  //  $this->server->downstreamConnection->address = $address;
                    $this->clientAddress = $address;
                }
                $this->server->getNetworkUtils()->writePacket($buffer, $this->server->upstreamConnection);
                break;
            case UnconnectedPong::$ID;
                ServerManager::updateServer($address, explode(';', substr($buffer, 40)));
                $this->server->getSocket()->send($buffer, $this->clientAddress->ip, $this->clientAddress->port);
                break;
            case OpenConnectionRequest1::$ID;
                $replacedAddress = $address->ip . ":" . $address->port;
                $this->getProxy()->getLogger()->info("New connection from " . TextFormat::AQUA . "[" . $replacedAddress . "]");
                //construct client connection
                $this->server->downstreamConnection = new DownstreamAbstractConnection($address, $this->server, $this->server->upstreamConnection);
                $this->server->upstreamConnection->setConnected(true);

                $this->server->getNetworkUtils()->writePacket($buffer, $this->server->upstreamConnection);
                break;
        }
    }

    /**
     * @return ProxyServer
     */
    public function getProxy(): ProxyServer {
        return $this->server;
    }
}