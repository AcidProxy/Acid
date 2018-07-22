<?php

declare(strict_types=1);

namespace proxy\hosts;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
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

    /**
     * ProxyClient constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
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
    public function hasValidUsername(string $username) : bool
    {
        return strlen($username) > 1 && strlen($username) <= 16 && $username !== "rcon" && $username !== "console";
    }

    /**
     * @param string $message
     */
    public function close(string $message) : void{
        $pk = new DisconnectPacket();
        $pk->message = $message;
        $this->getProxy()->getPacketSession()->writeDataPacket($pk, $this);
        $this->setConnected(false);
        $this->getProxy()->getSessionManager()->clientSessions[$this->getAddress()->ip]->setConnected(false);
    }

    /**
     * @param string $message
     * @param int $type
     */
    public function sendMessage(string $message, int $type = TextPacket::TYPE_RAW) : void{
        $pk = new TextPacket();
        $pk->type = $type;
        $pk->message = $message;
        $pk->source = $this->username;
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
    public function getName() : ?string{
        return $this->username;
    }

    /**
     * @return float
     */
    public function getX() : float{
        return $this->position->x;
    }

    /**
     * @return float
     */
    public function getY() : float {
        return $this->position->y;
    }

    /**
     * @return float
     */
    public function getZ() : float{
        return $this->position->z;
    }

    /**
     * @param Vector3 $position
     */
    public function setPosition(Vector3 $position){
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getGamemode() : int{
        return $this->gamemode;
    }

    /**
     * @param int $gamemode
     * @param bool $send
     */
    public function setGamemode(int $gamemode, bool $send = true){
        $this->gamemode = $gamemode;
        if($send){
            $pk = new SetPlayerGameTypePacket();
            $pk->gamemode = $gamemode;
            $this->getProxy()->getPacketSession()->writeDataPacket($pk, $this);
        }
    }
}