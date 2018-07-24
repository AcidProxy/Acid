<?php

declare(strict_types=1);

namespace proxy\raknet\client;

use proxy\ProxyServer;
use raklib\utils\InternetAddress;

/**
 * Class SessionManager
 * @package proxy\raknet\client
 */
class SessionManager
{

    /** @var Session[] $clientSessions */
    public $clientSessions = [];

    /** @var ProxyServer $proxyServer */
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
     * @return ProxyServer $server
     */
    public function getProxy(): ProxyServer
    {
        return $this->proxyServer;
    }

    /**
     * @param InternetAddress $address
     */
    public function addSession(InternetAddress $address)
    {
        $this->clientSessions[gethostbyname($address->ip)] = new Session($address);
    }


}