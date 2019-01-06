<?php

declare(strict_types=1);

namespace acidproxy;

use acidproxy\utils\InternetAddress;

/**
 * Class ServerManager
 * @package acidproxy
 */
class ServerManager {

    /** @var array $servers */
    private static $servers;

    /**
     * @param InternetAddress $address
     * @param array $data
     */
    public static function updateServer(InternetAddress $address, array $data) {
        $index = $address->ip . ":" . $address->port;
        if(!isset(self::$servers[$index])) {
            ProxyServer::getInstance()->getLogger()->info("Found new server [$address->ip:$address->port]: $data[0], MCBE v$data[2] ($data[3] players online)");
        }
        self::$servers[$index] = [
            "motd" => $data[0],
            "protocol" => $data[1],
            "version" => $data[2],
            "online" => $data[3],
            "entities" => []
        ];
    }
}