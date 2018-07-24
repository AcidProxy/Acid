<?php namespace proxy\hosts;


use pocketmine\network\mcpe\protocol\DataPacket;
use proxy\ProxyServer;
use raklib\utils\InternetAddress;

class BaseHost
{

    /**
     * @var InternetAddress $address
     */
    private $address;

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * @var DataPacket[] $packetQueue
     */
    public $packetQueue = [];

    /**
     * BaseHost constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
        $this->proxyServer = $proxyServer;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy(): ProxyServer
    {
        return $this->proxyServer;
    }

    /**
     * @return null|InternetAddress
     */
    public function getAddress(): ?InternetAddress
    {
        return $this->address;
    }

    /**
     * @param InternetAddress $address
     */
    public function setAddress(InternetAddress $address)
    {
        $this->address = $address;
    }

    /**
     * @param DataPacket $pk
     */
    public function dataPacket(DataPacket $pk)
    {
        $this->getProxy()->getPacketSession()->writeDataPacket($pk, $this);
    }

    /**
     * @param string $buffer
     */
    public function sendPacket(string $buffer)
    {
        $this->getProxy()->writePacket($buffer, $this->address->ip, $this->address->port);
    }


}