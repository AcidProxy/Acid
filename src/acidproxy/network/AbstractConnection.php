<?php

declare(strict_types=1);

namespace acidproxy\network;

use acidproxy\utils\InternetAddress;
use pocketmine\network\mcpe\protocol\DataPacket;

/**
 * Class AbstractConnection
 * @package acidproxy\network
 */
abstract class AbstractConnection {

    /** @var int $state */
    protected $state;

    /** @var InternetAddress $address */
    public $address;

    const STATE_DISCONNECTED = 0;
    const STATE_CONNECTING = 1;
    const STATE_CONNECTED = 2;

    public abstract function send(DataPacket $packet) : void;

    /**
     * @param DataPacket $packet
     * @return void
     */
    public abstract function handlePacket(DataPacket $packet) : void;

    /**
     * @return void
     */
    public abstract function disconnect() : void;

}