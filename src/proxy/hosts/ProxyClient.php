<?php

declare(strict_types=1);

namespace proxy\hosts;

use pocketmine\entity\Attribute;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\utils\TextFormat;
use proxy\command\sender\Sender;
use proxy\network\ClientNetworkSession;
use proxy\ProxyServer;

/**
 * Class ProxyClient
 * @package proxy\hosts
 */
class ProxyClient extends BaseHost implements Sender {

    /** @var ProxyServer $proxyServer */
    private $proxyServer;

    /** @var bool $isConnected */
    private $isConnected = false;

    /** @var string $username */
    private $username;

    /** @var Vector3 $position */
    private $position;

    /** @var int $gamemode */
    private $gamemode = 0;

    /** @var ClientNetworkSession $networkSession */
    private $networkSession;

    /** @var int $eid */
    private $eid;

    /** @var bool $fly */
    private $fly = false;

    /**
     * ProxyClient constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer) {
        $this->proxyServer = $proxyServer;
        $this->networkSession = new ClientNetworkSession($this, $proxyServer);
        parent::__construct($proxyServer);
    }

    /**
     * @param LoginPacket $loginPacket
     */
    public function handleLogin(LoginPacket $loginPacket){
        if(!$this->hasValidUsername($loginPacket->username)){
            $this->close("Invalid username");
            return;
        }
        $this->username = TextFormat::clean($loginPacket->username);
    }

    /**
     * @param string $username
     * @return bool
     */
    public function hasValidUsername(string $username): bool {
        return strlen($username) > 1 && strlen($username) <= 16 && $username !== "rcon" && $username !== "console";
    }

    /**
     * @param string $message
     */
    public function close(string $message): void {
        $pk = new DisconnectPacket();
        $pk->message = $message;
        $this->getProxy()->getPacketSession()->writeDataPacket($pk, $this);
        $this->setConnected(false);
        $this->getProxy()->getSessionManager()->clientSessions[$this->getAddress()->ip]->setConnected(false);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function sendMessage(string $message): void {
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_RAW;
        $pk->message = $message;
        $this->dataPacket($pk);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function sendPopup(string $message): void {
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_POPUP;
        $pk->message = $message;
        $this->dataPacket($pk);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function sendTip(string $message): void {
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_TIP;
        $pk->message = $message;
        $this->dataPacket($pk);
    }

    /**
     * @param string $message
     */
    public function sendWhisper(string $message): void {
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_WHISPER;
        $pk->message = $message;
        $this->dataPacket($pk);
    }


    /**
     * @return ClientNetworkSession
     */
    public function getNetworkSession() : ClientNetworkSession{
        return $this->networkSession;
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

    /**
     * @return string
     */
    public function getName(): ?string{
        return $this->username;
    }

    /**
     * @return float $x
     */
    public function getX(): float{
        return $this->position->x;
    }

    /**
     * @return float $y
     */
    public function getY(): float {
        return $this->position->y;
    }

    /**
     * @return float $z
     */
    public function getZ(): float{
        return $this->position->z;
    }

    /**
     * @return Vector3 $position
     */
    public function asVector3(): Vector3 {
        return new Vector3($this->getX(), $this->getY(), $this->getZ());
    }

    /**
     * @param Vector3 $position
     */
    public function setPosition(Vector3 $position) {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getGamemode(): int {
        return $this->gamemode;
    }

    /**
     * @param int $gamemode
     * @param bool $send
     */
    public function setGamemode(int $gamemode, bool $send = true) {
        $this->gamemode = $gamemode;
        if($send){
            $pk = new SetPlayerGameTypePacket();
            $pk->gamemode = $gamemode;
            $this->getProxy()->getPacketSession()->writeDataPacket($pk, $this);
        }
    }

    /**
     * @param int $eid
     */
    public function setEntityRuntimeId(int $eid) {
        $this->eid = $eid;
    }

    /**
     * @return int $eid
     */
    public function getEntityRuntimeId(): int {
        return (int)$this->eid;
    }

    /**
     * @param int $maxHealth
     */
    public function setMaxHealth(int $maxHealth) {
        $attribute = Attribute::getAttribute(Attribute::HEALTH);
        $attribute->setValue($maxHealth);

        $pk = new UpdateAttributesPacket();
        $pk->entityRuntimeId = $this->getEntityRuntimeId();
        $pk->entries[] = $attribute;

        $this->dataPacket($pk);
    }

    /**
     * @param int $food
     */
    public function setFood(int $food) {
        $attribute = Attribute::getAttribute(Attribute::FOOD);
        $attribute->setValue($food);

        $pk = new UpdateAttributesPacket();
        $pk->entityRuntimeId = $this->getEntityRuntimeId();
        $pk->entries[] = $attribute;

        $this->dataPacket($pk);
    }

    /**
     * @param bool $fly
     * @param bool $send
     *
     * @return bool $canFly
     */
    public function setAllowFly(bool $fly = true, bool $send = true): bool {
        $this->fly = $fly;
        if($send) {
            $pk = new AdventureSettingsPacket();
            $pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $fly);
            $pk->commandPermission = AdventureSettingsPacket::PERMISSION_OPERATOR;
            $pk->playerPermission = PlayerPermissions::OPERATOR;
            $pk->entityUniqueId = $this->getEntityRuntimeId();
            $this->dataPacket($pk);
            return true;
        }
        return $this->fly;
    }

    /**
     * @return bool $canFly
     */
    public function getAllowFly(): bool {
        return $this->fly;
    }

}