<?php

declare(strict_types=1);

namespace acidproxy\utils;

/**
 * Class InternetAddress
 * @package proxy\utils
 */
class InternetAddress {

    /** @var string $ip */
    public $ip;

    /** @var int $port */
    public $port;

    /**
     * InternetAddress constructor.
     * @param string $ip
     * @param int $port
     */
    public function __construct(string $ip, int $port) {
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * @param InternetAddress $address
     * @return bool
     */
    public function equals(InternetAddress $address) {
        return $this->ip == $address->ip && $this->port == $address->port;
    }
}