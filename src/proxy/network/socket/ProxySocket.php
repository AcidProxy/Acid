<?php

declare(strict_types=1);

namespace proxy\network\socket;

/**
 * Class ProxySocket
 * @package proxy\network
 */
class ProxySocket
{

    /** @var SocketManager $socketMgr */
    public $socketMgr;

    /** @var resource $socket */
    private $socket;

    /**
     * ProxySocket constructor.
     * @param SocketManager $socketMgr
     */
    public function __construct(SocketManager $socketMgr)
    {
        $this->socketMgr = $socketMgr;
    }

    /**
     * @param int $domain
     * @param int $type
     * @param int $protocol
     */
    public function create(int $domain, int $type, int $protocol)
    {
        $this->socket = socket_create($domain, $type, $protocol);
    }

    /**
     * @param string $address
     * @param int $port
     *
     * @return bool
     */
    public function bind(string $address, int $port): bool
    {
        return (bool)socket_bind($this->socket, $address, $port);
    }

    /**
     * @param int $level
     * @param int $option
     * @param $value
     */
    public function setOption(int $level, int $option, $value)
    {
        socket_set_option($this->socket, $level, $option, $value);
    }

    public function send()
    {
        foreach ($this->socketMgr->toSend as $index => [$buffer, $address, $port]) {
            @socket_sendto($this->socket, $buffer, strlen($buffer), 0, $address, $port);
            unset($this->socketMgr->toSend[$index]);
        }
    }

    public function receive()
    {
        if (@socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port) && is_string($buffer) && strlen($buffer) > 2) {
            $this->socketMgr->received[] = [$buffer, $address, $port];
        }
    }
}