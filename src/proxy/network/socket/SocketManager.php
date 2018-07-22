<?php

declare(strict_types=1);

namespace proxy\network\socket;

use proxy\ProxyServer;
use proxy\utils\AsyncLogger;

/**
 * Class SocketManager
 * @package proxy\network
 */
class SocketManager extends \Thread {

    /** @var ProxyServer $server */
    public $server;

    /** @var bool $stop */
    public $stop = false;

    /** @var \array $toSend */
    public $toSend = [];

    /** @var \array $received */
    public $received = [];

    /** @var \array $connection */
    public $connection;

    /**
     * SocketManager constructor.
     * @param ProxyServer $server
     * @param array $connection
     */
    public function __construct(ProxyServer $server, array $connection) {
        $this->server = $server;
        $this->connection = $connection;
    }

    /**
     * @param $buffer
     * @param $address
     * @param $port
     */
    public function addPacketToSendQueue($buffer, $address, $port) {
        $this->toSend[] = [$buffer, $address, $port];
    }


    public function run() {
        // classloader
        require COMPOSER;
        // logger
        $logger = new AsyncLogger("Socket thread");

        $socket = new ProxySocket($this);
        $socket->create(AF_INET, SOCK_DGRAM, SOL_UDP);

        /** @var string $bindAddress */
        $bindAddress = (string)$this->connection["address"];
        /** @var int $bindPort */
        $bindPort = (int)$this->connection["port"];

        if($socket->bind($bindAddress, $bindPort)) {
            $logger->info("§aSuccessfully bind to {$bindAddress}:{$bindPort}");
        }
        else {
            $logger->error("§cFailed to bind socket on {$bindAddress}:{$bindPort}");
        }

        $socket->setOption(SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
        $socket->setOption(SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);

        while ($this->stop !== true) {
            echo "SM\n";
            $socket->receive();
            $socket->send();
        }
    }
}