<?php

namespace proxy;

use pocketmine\level\format\Chunk;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\protocol\TextPacket;
use pocketmine\scheduler\ServerScheduler;
use pocketmine\utils\Terminal;
use pocketmine\utils\TextFormat;
use proxy\command\ProxyCommandMap;
use proxy\hosts\BaseHost;
use proxy\hosts\Client;
use proxy\hosts\ProxyClient;
use proxy\hosts\TargetServer;
use proxy\plugin\PluginManager;
use proxy\raknet\client\SessionManager;
use proxy\scheduler\ProxyScheduler;
use proxy\scheduler\TastTask;
use proxy\scheduler\TestTask;
use proxy\utils\Logger;
use proxy\utils\PacketSession;
use raklib\protocol\DATA_PACKET_4;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;
use raklib\utils\InternetAddress;

class ProxyServer
{

    public $socket;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var TargetServer $targetServer
     */
    private $targetServer;

    /**
     * @var ProxyScheduler $proxyScheduler
     */
    private $proxyScheduler;

    /**
     * @var ProxyClient $proxyClient
     */
    private $proxyClient;

    /**
     * @var PacketSession $packetSession
     */
    private $packetSession;

    /**
     * @var SessionManager $sessionManager
     */
    private $sessionManager;

    /**
     * @var PluginManager $pluginManager
     */
    private $pluginManager;

    /**
     * @var ProxyCommandMap $commandMap
     */
    private $commandMap;

    CONST SERVER_API = "1.0.0";

    /**
     * ProxyServer constructor.
     * @param string $serverAddress
     * @param int $serverPort
     * @param string $bindPort
     * @param string $bindAddress
     * @throws \Exception
     */
    public function __construct(string $serverAddress, int $serverPort, string $bindPort, string $bindAddress = '0.0.0.0')
    {
        $startTime = microtime(true);
        $this->logger = new Logger($this);
        $text = str_repeat(Terminal::$COLOR_GRAY . "=", 35) . PHP_EOL .Terminal::$COLOR_AQUA. " ____  ____  ____ ___  ____  _
/  __\/  __\/  _ \\  \//\  \//
|  \/||  \/|| / \| \  /  \  / 
|  __/|    /| \_/| /  \  / /  
\_/   \_/\_\\____//__/\\/_/   
                              ";
        echo Terminal::$COLOR_AQUA .$text . PHP_EOL .  str_repeat(Terminal::$COLOR_GRAY . "=", 35) . PHP_EOL;
        $this->getLogger()->info(TextFormat::AQUA . "Starting on " . $bindAddress . ":" . $bindPort);
        $this->getLogger()->info(TextFormat::YELLOW . "Author: " . TextFormat::GRAY . "kaliiks");
        $this->getLogger()->info(TextFormat::YELLOW . "Discord: " . TextFormat::GRAY . "kaliiks#0921" . PHP_EOL);
        $this->proxyScheduler = new ProxyScheduler();
        $this->proxyClient = new ProxyClient($this);
        $this->packetSession = new PacketSession($this);
        $this->sessionManager = new SessionManager($this);
        $this->targetServer = new TargetServer($this, new InternetAddress(gethostbyname($serverAddress), $serverPort, 2));
        $this->pluginManager = new PluginManager($this);
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        PacketPool::init();

        if(!socket_bind($this->socket, $bindAddress, $bindPort)) {
            $this->getLogger()->error("Failed to bind socket on {$bindAddress}:{$bindPort}");
            exit(127);
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
        $this->getLogger()->info(TextFormat::YELLOW . "Starting workers...");
        $this->startThreadWorkers();
        $this->pluginManager->loadPlugins("plugins");
        $this->getLogger()->info(TextFormat::GREEN . "Proxy started in " . round((microtime(true) - $startTime),3) . " seconds");
        $this->getLogger()->info(TextFormat::GREEN . "Waiting for UnconnetedPing..." . PHP_EOL);
        $this->commandMap = new ProxyCommandMap($this->pluginManager, $this);

        while(true){
            if(@socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port) !== false){
                   $compareAddress = new InternetAddress(gethostbyname($address), $port, 2);
                   if(!$this->getClient()->isConnected()){
                       $this->handleRakNetPacket($buffer, $compareAddress);
                   }else{
                       if($compareAddress->equals($this->getTargetServer()->getAddress())){
                               $this->getPacket($buffer, $this->getTargetServer());
                               $this->getClient()->sendPacket($buffer);
                       }elseif($compareAddress->equals($this->getClient()->getAddress())){
                               $this->getPacket($buffer, $this->getClient());
                               $this->getTargetServer()->sendPacket($buffer);
                       }
                   }
            }
        }
    }


    /**
     * @param string $buffer
     * @param BaseHost $host
     * @return bool
     */
    public function getPacket(string $buffer, BaseHost $host) : bool {
        if(($packet = $this->packetSession->readDataPacket($buffer)) !== null){
            if($host instanceof ProxyClient){
                $this->getClient()->getNetworkSession()->handleClientDataPacket($packet);
            }else{
                $this->getClient()->getNetworkSession()->handleServerDataPacket($packet);
            }
            return true;
        }
        return false;
    }


    /**
     * @param string $buffer
     */
    private function handleRakNetPacket(string $buffer, InternetAddress $compareAddress) : void{
        switch(ord($buffer{0})){
            case UnconnectedPing::$ID;
                if(!isset($this->sessionManager->clientSessions[gethostbyname($compareAddress->ip)])){
                    $this->sessionManager->addSession($compareAddress);
                }
                if(!$this->getClient()->isConnected()){
                    $this->getClient()->setAddress($compareAddress);
                }
                $this->getTargetServer()->sendPacket($buffer);
                break;
            case UnconnectedPong::$ID;
                $serverInfo = explode(';', substr($buffer, 40));
                if(empty($this->getTargetServer()->getInformation())){
                    $this->getLogger()->info(TextFormat::YELLOW . "SERVER INFROMATION");
                    $this->getLogger()->info(TextFormat::AQUA . "PLAYERS: " . $serverInfo[3]);
                    $this->getLogger()->info(TextFormat::AQUA . "MCPE: " . $serverInfo[2]);
                    $this->getLogger()->info(TextFormat::AQUA . "PROTOCOL: " . $serverInfo[1]);
                    $this->getLogger()->info(TextFormat::AQUA . "MOTD: " . $serverInfo[0] . PHP_EOL);
                }
                $this->getTargetServer()->setInformation($serverInfo);
                foreach($this->sessionManager->clientSessions as $session){
                    if(!$session->isConnected()){
                        $this->writePacket($buffer, $session->getAddress()->ip, $session->getAddress()->port);
                    }
                }
                break;
            case OpenConnectionRequest1::$ID;
                $replacedAddress = str_replace(" " , ":", $compareAddress->toString());
                $this->getLogger()->info(TextFormat::YELLOW . "New connection from " . TextFormat::AQUA . "[" . $replacedAddress . "]" . PHP_EOL);
                $this->getClient()->setAddress($compareAddress);
                $this->getClient()->setConnected(true);
                $this->sessionManager->clientSessions[gethostbyname($compareAddress->ip)]->setConnected(true);
                $server = $this->getTargetServer()->getAddress();
                $this->writePacket($buffer, $server->ip, $server->port);
                break;
        }
    }

    private function startThreadWorkers() : void{
        try{
            $packetThread = new ServerThread($this);
            $packetThread->start(PTHREADS_INHERIT_ALL);
            $this->getLogger()->info(TextFormat::GREEN . "Started 1 thread worker(s)" . PHP_EOL);
        }catch (\Exception $e){
            $this->getLogger()->error($e->getMessage());
        }
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager() : PluginManager{
        return $this->pluginManager;
    }

    /**
     * @return ProxyCommandMap
     */
    public function getCommandMap() : ProxyCommandMap{
        return $this->commandMap;
    }

    /**
     * @return SessionManager
     */
    public function getSessionManager() : SessionManager{
        return $this->sessionManager;
    }

    /**
     * @return ProxyClient
     */
    public function getClient() : ProxyClient{
        return $this->proxyClient;
    }

    /**
     * @return PacketSession
     */
    public function getPacketSession() : PacketSession{
        return $this->packetSession;
    }

    /**
     * @return ProxyScheduler
     */
    public function getScheduler() : ProxyScheduler{
        return $this->proxyScheduler;
    }

    /**
     * @return TargetServer
     */
    public function getTargetServer() : TargetServer{
        return $this->targetServer;
    }

    /**
     * @param string $buffer
     * @param string $host
     * @param int $port
     */
    public function writePacket(string $buffer, string $host, int $port) : void{
        socket_sendto($this->socket, $buffer, strlen($buffer), 0, $host, $port);
    }

    /**
     * @return Logger
     */
    public function getLogger() : Logger{
        return $this->logger;
    }

}