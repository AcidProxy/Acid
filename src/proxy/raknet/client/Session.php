<?php namespace proxy\raknet\client;


use raklib\utils\InternetAddress;

class Session
{

    /**
     * @var InternetAddress $address
     */
    private $address;

    /**
     * @var bool $isConnected
     */
    private $isConnected = false;

    /**
     * Session constructor.
     * @param InternetAddress $address
     */
    public function __construct(InternetAddress $address)
    {
        $this->address = $address;
    }

    /**
     * @return InternetAddress
     */
    public function getAddress() : InternetAddress{
        return $this->address;
    }
    /**
     * @return bool
     */
    public function isConnected() : bool{
        return $this->isConnected;
    }

    /**
     * @param bool $isConnected
     */
    public function setConnected(bool $isConnected){
        $this->isConnected = $isConnected;
    }

}