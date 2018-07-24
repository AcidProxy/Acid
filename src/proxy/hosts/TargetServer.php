<?php namespace proxy\hosts;


use proxy\ProxyServer;
use raklib\utils\InternetAddress;

class TargetServer extends BaseHost
{

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * @var $information
     */
    private $information = [];

    /**
     * TargetServer constructor.
     * @param ProxyServer $proxyServer
     * @param InternetAddress $address
     */
    public function __construct(ProxyServer $proxyServer, InternetAddress $address)
    {
        $this->proxyServer = $proxyServer;
        $this->setAddress($address);
        parent::__construct($proxyServer);
    }

    /**
     * @param array $iformation
     */
    public function setInformation(array $iformation)
    {
        $this->information = $iformation;
    }

    /**
     * @return array
     */
    public function getInformation(): array
    {
        return $this->information;
    }
}