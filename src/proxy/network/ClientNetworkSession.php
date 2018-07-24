<?php

namespace proxy\network;


use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MoveEntityAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\utils\Terminal;
use pocketmine\utils\TextFormat;
use proxy\command\Command;
use proxy\hosts\Client;
use proxy\hosts\ProxyClient;
use proxy\plugin\PluginBase;
use proxy\ProxyServer;
use proxy\utils\PacketSession;

class ClientNetworkSession
{

    /**
     * @var ProxyServer $proxyServer
     */
    private $proxyServer;

    /**
     * @var ProxyClient $client
     */
    private $client;

    /**
     * ClientNetworkSession constructor.
     * @param ProxyClient $client
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyClient $client, ProxyServer $proxyServer)
    {
        $this->client = $client;
        $this->proxyServer = $proxyServer;
    }

    /**
     * @return ProxyServer
     */
    public function getProxy() : ProxyServer{
        return $this->proxyServer;
    }

    /**
     * @return ProxyClient
     */
    public function getClient() : ProxyClient{
        return $this->client;
    }

    /**
     * @param DataPacket $packet
     * @return bool
     */
    public function handleClientDataPacket(DataPacket $packet): bool {
        $packet->decode();
        if (!$packet->feof() && !$packet->mayHaveUnreadBytes()) {
            $remains = substr($packet->buffer, $packet->offset);
            echo Terminal::$COLOR_BLUE . "Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains) . PHP_EOL;
            return true;
        }
        switch ($packet::NETWORK_ID) {
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                $this->getClient()->handleLogin($packet);
                break;
            case MovePlayerPacket::NETWORK_ID:
                /** @var MovePlayerPacket $packet */
                $this->getClient()->setPosition($packet->position);
                break;
            case SetPlayerGameTypePacket::NETWORK_ID:
                /** @var SetPlayerGameTypePacket $packet */
                $this->getClient()->setGamemode($packet->gamemode, false);
                break;
            case StartGamePacket::NETWORK_ID:
                /** @var StartGamePacket $packet */
                $this->getClient()->setGamemode($packet->worldGamemode, false);
                break;
            case SetLocalPlayerAsInitializedPacket::NETWORK_ID:
                /** @var SetLocalPlayerAsInitializedPacket $packet */
                $this->getClient()->setEntityRuntimeId($packet->entityRuntimeId);
                break;
            case TextPacket::NETWORK_ID:
                $cmd = "*/";
                /** @var TextPacket $packet */
                if ($packet->type == TextPacket::TYPE_CHAT) {
                    if(substr($packet->message, 0, 2) == $cmd) {
                        $args = explode(" ", substr($packet->message, 2));
                        $commandName = $args[0];
                        array_shift($args);
                        $this->getProxy()->getCommandMap()->getCommand($commandName)->execute($this->getClient(), $args);
                    }
                    /*
                     * TOO SLOW WAY
                    foreach ($this->getProxy()->getCommandMap()->getCommands() as $command => $object) {
                        $args = explode(" ", $packet->message);
                        if (strtolower($args[0]) == "*./" . strtolower($command)) {
                            $object->execute($this->getClient(), $args);
                        } elseif (strpos($cmd, $packet->message) !== false) {
                            $this->getClient()->sendMessage("• " . TextFormat::AQUA . "Unknown command issued. Type " . TextFormat::WHITE . "*./help " . TextFormat::AQUA . " for list of all commands");
                        }
                    }*/
                }
                break;
        }
        /**
         * @var PluginBase $plugin
         */
        foreach ($this->getProxy()->getPluginManager()->getPlugins() as $plugin) {
            if ($plugin->isEnabled()) {
                $plugin->handlePacketSend($packet); //TODO: return bool
            }
        }
        return true;
    }

    /**
     * @param DataPacket $packet
     */
    public function handleServerDataPacket(DataPacket $packet){
        $packets = [SetPlayerGameTypePacket::NETWORK_ID, StartGamePacket::NETWORK_ID];
        switch($packet::NETWORK_ID){
            case TextPacket::NETWORK_ID;
                /** @var TextPacket $packet */
                 $packet->decode();
                 if($packet->type == TextPacket::TYPE_RAW ||
                    $packet->type == TextPacket::TYPE_SYSTEM ||
                    $packet->type == TextPacket::TYPE_TRANSLATION ||
                    $packet->type == TextPacket::TYPE_ANNOUNCEMENT){
                     $this->getProxy()->getLogger()->info($packet->message);
                 }
                 break;
            case SetPlayerGameTypePacket::NETWORK_ID;
                /** @var SetPlayerGameTypePacket $packet */
                $packet->decode();
                $this->getClient()->setGamemode($packet->gamemode, false);
                break;
            case StartGamePacket::NETWORK_ID;
                /** @var StartGamePacket $packet */
                $packet->decode();
                if($packet->worldGamemode === null) {
                    $this->getClient()->setGamemode(1, false);
                    break;
                }
                $this->getClient()->setGamemode($packet->worldGamemode, false);
            break;
        }
        /** @var PluginBase $plugin */
        foreach($this->getProxy()->getPluginManager()->getPlugins() as $plugin){
            if($plugin->isEnabled()){
                $plugin->handlePacketReceive($packet);
            }
        }

    }

}