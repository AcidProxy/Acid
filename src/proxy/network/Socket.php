<?php

namespace proxy\network;


use proxy\utils\Logger;
use raklib\utils\InternetAddress;

/**
 * Class Socket
 * @package proxy\network
 */
class Socket extends \Thread
{

    /**
     * @var InternetAddress $address
     */
    private $address;

    /**
     * @var \resource $socket
     */
    private $socket;

    /**
     * @var int $maxConnections
     */
    private $maxConnections;

    /**
     * @var bool $closed
     */
    private $closed = false;

    /**
     * @var array $bufferQueue
     */
    public $bufferQueue = [];

    /**
     * Socket constructor.
     * @param int $maxConnections
     */
    public function __construct(int $maxConnections)
    {
        $this->maxConnections = $maxConnections;
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function run() : void{
         while(!$this->closed){
             $this->receiveBuffer();
         }
    }

    /**
     * @param InternetAddress $address
     * @throws \Exception
     * @return void
     */
    public function bind(InternetAddress $address) : void{
        if($this->address !== null){
            throw new \Exception("Socket is already bound");
        }

        try{
            socket_bind($this->socket, $address->ip, $address->port);
            Logger::log("Bound to " . $address->ip . ":" . $address->port);
        }catch (\Exception $exception){
            Logger::log("Failed to bind to " . $address->ip . ":" . $address->port);
            exit(-1);
        }

        $this->address = $address;
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function receiveBuffer() : void{
        try{
            @socket_recvfrom($this->socket,$buffer, 65535, 0, $from, $port);
            $this->bufferQueue[] = [$buffer, $from, $port];
        }catch (\Exception $exception){
            error_log("Failed to receive buffer from {$from}:{$port}");
        }


    }

    /**
     * @param string $buffer
     * @param $to
     * @param $port
     * @param int $flags
     */
    public function writeBuffer(string $buffer, $to, $port, $flags = 0) : void{
         socket_sendto($this->socket, $buffer, strlen($buffer), $flags, $to, $port);
    }


    /**
     * @throws \Exception
     * @return void
     */
    public function close() : void{
        if($this->socket == null){
            throw new \Exception("Failed to close inactive socket");
        }
        $this->socket = true;
        socket_close($this->socket);
    }



}