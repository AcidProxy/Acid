<?php

namespace proxy\network;


use pocketmine\utils\TextFormat;
use proxy\ProxyServer;
use proxy\utils\Logger;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;
use raklib\utils\InternetAddress;

class SocketListener
{

    /**
     * @var ProxyServer $server
     */
    private $server;

    /**
     * @var InternetAddress $clientAddress
     */
    private $clientAddress;

    /**
     * SocketListener constructor.
     * @param ProxyServer $server
     */
    public function __construct(ProxyServer $server)
    {
        $this->server = $server;
    }

    /**
     * @param string $buffer
     * @param InternetAddress $address
     */
    public function listen(string $buffer, InternetAddress $address) : void
    {
        switch (ord($buffer{0})) {
            case UnconnectedPing::$ID;
                if (!$this->server->upstreamConnection->isConnected()) {
                  //  $this->server->downstreamConnection->address = $address;
                    $this->clientAddress = $address;
                }
                $this->server->getNetworkUtils()->writePacket($buffer, $this->server->upstreamConnection);
                break;
            case UnconnectedPong::$ID;
                $serverInfo = explode(';', substr($buffer, 40));
                Logger::log(TextFormat::YELLOW . "SERVER INFROMATION");
                Logger::log(TextFormat::AQUA . "PLAYERS: " . $serverInfo[3]);
                Logger::log(TextFormat::AQUA . "MCPE: " . $serverInfo[2]);
                Logger::log(TextFormat::AQUA . "PROTOCOL: " . $serverInfo[1]);
                Logger::log(TextFormat::AQUA . "MOTD: " . $serverInfo[0] . PHP_EOL);
                $this->server->getSocket()->writeBuffer($buffer, $this->clientAddress->ip, $this->clientAddress->port);
                break;
            case OpenConnectionRequest1::$ID;
                $replacedAddress = str_replace(" ", ":", $address->toString());
                Logger::log(TextFormat::YELLOW . "New connection from " . TextFormat::AQUA . "[" . $replacedAddress . "]" . PHP_EOL);
                //construct client connection
                $this->server->downstreamConnection = new DownstreamAbstractConnection($address, $this->server, $this->server->upstreamConnection);
                $this->server->upstreamConnection->setConnected(true);

                $this->server->getNetworkUtils()->writePacket($buffer, $this->server->upstreamConnection);
                break;
        }
    }

}