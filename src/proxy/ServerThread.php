<?php namespace proxy;


use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\utils\Terminal;
use raklib\utils\InternetAddress;

class ServerThread extends \Thread
{

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * PacketThread constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
        $this->proxyServer = $proxyServer;
    }

    public function run(){

    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }



}