<?php

declare(strict_types=1);

namespace acidproxy;

use acidproxy\utils\InternetAddress;

/**
 * Class ProxyUDPSocket
 * @package proxy\network
 */
class ProxyUDPSocket {

    /** @var resource $socket */
    protected $socket;

    /** @var InternetAddress $bindAddress */
    protected $bindAddress;

    /**
     * ProxyUDPSocket constructor.
     */
    public function __construct() {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
    }

    /**
     * @param InternetAddress $address
     * @throws \Exception
     */
    public function bind(InternetAddress $address) {
        if(socket_bind($this->socket, $address->ip, $address->port)) {
            ProxyServer::getInstance()->getLogger()->info("Successfully bound to {$address->ip}:{$address->port}");
        }
        else {
            throw new \Exception("Could not bound to {$address->ip}:{$address->port}");
        }
    }

    /**
     * @param string|null $buffer
     * @param string|null $ip
     * @param int|null $port
     */
    public function receive(?string &$buffer, ?string &$ip, ?int &$port) {
        socket_recvfrom($this->socket, $buffer, 65535, 0, $ip, $port);
    }

    /**
     * @param string $buffer
     * @param string $ip
     * @param int $port
     */
    public function send(string $buffer, string $ip, int $port) {
        socket_sendto($this->socket, $buffer, strlen($buffer), 0, $ip, $port);
    }

    public function close() {
        socket_close($this->socket);
    }
}