<?php

namespace proxy\raknet\client;


use proxy\ProxyServer;
use raklib\utils\InternetAddress;

class SessionManager
{

    /**
     * @var Session[] $clientSessions
     */
    public $clientSessions = [];

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * SessionManager constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
        $this->proxyServer = $proxyServer;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }

    /**
     * @param InternetAddress $address
     */
    public function addSession(InternetAddress $address){
        $this->clientSessions[gethostbyname($address->ip)] = new Session($address);
    }


}