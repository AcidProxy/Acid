<?php

declare(strict_types=1);

namespace acidproxy;

use acidproxy\command\sender\Sender;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\utils\TextFormat;

/**
 * Class Client
 * @package acidproxy
 */
class Client implements Sender {

    public const SURVIVAL = 0;
    public const CREATIVE = 1;
    public const ADVENTURE = 2;
    public const SPECTATOR = 3;

    /** @var ProxyServer $server */
    private $server;

    /** @var bool $isConnected */
    private $isConnected = false;

    /** @var int $protocol */
    private $protocol = ProtocolInfo::CURRENT_PROTOCOL;

    /** @var string $xuid */
    private $xuid = "";

    /** @var string $uuid */
    private $uuid = "";

    /** @var int $id */
    private $id;

    /** @var string $name */
    private $name = "";

    /** @var int $entityRuntimeId */
    private $entityRuntimeId = 0;

    /** @var int $entityUniqueId */
    private $entityUniqueId = 0;

    /** @var int $gamemode */
    private $gamemode = 0;

    /** @var bool $allowFly */
    private $allowFly = false;

    /**
     * Client constructor.
     * @param ProxyServer $server
     */
    public function __construct(ProxyServer $server) {
        $this->server = $server;
    }

    # >
    # >> Handlers
    # >

    /**
     * @param LoginPacket $pk
     */
    public function handleLogin(LoginPacket $pk) {
        $this->protocol = $pk->protocol;
        $this->xuid = $pk->xuid;
        $this->name = TextFormat::clean($pk->username);
        $this->uuid = $pk->clientUUID;
        $this->id = $pk->clientId;
        $this->setConnected(true);
    }

    /**
     * @param StartGamePacket $pk
     */
    public function handleStartGame(StartGamePacket $pk) {
        $this->entityRuntimeId = $pk->entityRuntimeId;
        $this->entityUniqueId = $pk->entityUniqueId;
        $this->gamemode = $pk->playerGamemode;
    }

    # >
    # >> Proxy-API methods
    # >

    /**
     * @param bool $connected
     */
    public function setConnected(bool $connected = true) {
        $this->isConnected = $connected;
    }

    /**
     * @return int
     */
    public function getProtocol(): int {
        return $this->protocol;
    }

    # >
    # >> Other api methods
    # >


    /**
     * @api
     *
     * @param DataPacket $pk
     */
    public function dataPacket(DataPacket $pk) {
        $this->getProxy()->getNetworkUtils()->writeDataPacket($pk, $this->getProxy()->downstreamConnection);
    }

    /**
     * @api
     *
     * @param string $message
     */
    public function sendMessage(string $message) {
        $pk = new TextPacket();
        $pk->message = $message;
        $pk->type = TextPacket::TYPE_CHAT;
        $this->dataPacket($pk);
    }

    /**
     * @api
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @api
     *
     * @return string
     */
    public function getUUID(): string {
        return $this->uuid;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @api
     *
     * @return string
     */
    public function getIp(): string {
        return $this->getProxy()->downstreamConnection->address->ip;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getPort(): int {
        return $this->getProxy()->downstreamConnection->address->port;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getGamemode(): int {
        return $this->gamemode;
    }

    /**
     * @api
     *
     * @param int $gamemode
     * @param bool $send
     */
    public function setGamemode(int $gamemode = 0, bool $send = true) {
        $this->gamemode = $gamemode;

        if(!$send) return;
        $pk = new SetPlayerGameTypePacket();
        $pk->gamemode;
        $this->dataPacket($pk);
    }

    /**
     * @api
     *
     * @return bool
     */
    public function getAllowFly(): bool {
        return $this->allowFly;
    }

    /**
     * @api
     *
     * @param bool $fly
     * @param bool $send
     */
    public function setAllowFly(bool $fly, bool  $send) {
        $this->allowFly = $fly;

        if(!$send) return;
        $pk = new AdventureSettingsPacket();
        $pk->entityUniqueId = $this->getEntityUniqueId();
        $pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $fly);
    }

    /**
     * @api
     *
     * @return int
     */
    public function getEntityRuntimeId(): int {
        return $this->entityRuntimeId;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getEntityUniqueId(): int {
        return $this->entityUniqueId;
    }

    /**
     * @api
     *
     * @return ProxyServer
     */
    public function getProxy(): ProxyServer {
        return $this->server;
    }
}