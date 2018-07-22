<?php

declare(strict_types=1);

namespace proxy;

use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\TextFormat;
use proxy\command\CommandMap;
use proxy\hosts\BaseHost;
use proxy\hosts\ProxyClient;
use proxy\hosts\TargetServer;
use proxy\network\socket\SocketManager;
use proxy\plugin\PluginManager;
use proxy\raknet\client\SessionManager;
use proxy\scheduler\ProxyScheduler;
use proxy\utils\Logger;
use proxy\utils\PacketSession;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;
use raklib\utils\InternetAddress;

/**
 * Class ProxyServer
 * @package proxy
 */
class ProxyServer {

    public const PLUGINS_DIR = "plugins";
    public const SERVER_API = "1.0.0";

    /** @var bool $running */
    protected $running = true;

    /** @var Logger $logger */
    private $logger;

    /** @var SocketManager $socketMgr */
    private $socketMgr;

    /** @var TargetServer $targetServer */
    private $targetServer;

    /** @var ProxyScheduler $proxyScheduler */
    private $proxyScheduler;

    /** @var ProxyClient $proxyClient */
    private $proxyClient;

    /** @var PacketSession $packetSession */
    private $packetSession;

    /** @var SessionManager $sessionManager */
    private $sessionManager;

    /** @var PluginManager $pluginManager */
    private $pluginManager;

    /** @var CommandMap $commandMap */
    private $commandMap;

    /**
     * ProxyServer constructor.
     * @param string $serverAddress
     * @param int $serverPort
     * @param int $bindPort
     * @param string $bindAddress
     * @throws \Exception
     */
    public function __construct(string $serverAddress, int $serverPort, int $bindPort, string $bindAddress = '0.0.0.0') {
        $startTime = microtime(true);

        $connection["address"] = $bindAddress;
        $connection["port"] = $bindPort;

        $this->logger = new Logger($this);
        $this->socketMgr = new SocketManager($this, $connection);
        $this->proxyScheduler = new ProxyScheduler();
        $this->proxyClient = new ProxyClient($this);
        $this->packetSession = new PacketSession($this);
        $this->sessionManager = new SessionManager($this);
        $this->targetServer = new TargetServer($this, new InternetAddress(gethostbyname($serverAddress), $serverPort, 2));
        $this->pluginManager = new PluginManager($this);
        $this->commandMap = new CommandMap($this);

        PacketPool::init();

        $this->startThreadWorkers();

        $this->pluginManager->loadPlugins(self::PLUGINS_DIR);
        $this->getLogger()->info(TextFormat::GREEN . "Proxy started in " . round((microtime(true) - $startTime),3) . " seconds");

        $this->tickProcessor();
    }

    public function tickProcessor(): void {
        while($this->running === true) {
            $this->tick();
        }
    }

    public function tick(): void {
        $this->tickNetwork();
        $this->tickCommands();
    }

    private function tickCommands(): void {
        $this->commandMap->tick();
    }

    private function tickNetwork(): void {
        foreach ($this->socketMgr->received as $index => $received) {
            /** @var string $buffer */
            $buffer = $received[0];
            /** @var string $address */
            $address = $received[1];
            /** @var int $port */
            $port = $received[2];

            if(is_null($buffer) || is_null($address) || is_null($port)) return;

            $compareAddress = new InternetAddress(gethostbyname($address), $port, 2);

            if (!$this->getClient()->isConnected()) {
                $this->handleRakNetPacket($buffer, $compareAddress);
            }
            else {
                if ($compareAddress->equals($this->getTargetServer()->getAddress())) {
                    $this->getPacket($buffer, $this->getTargetServer());
                    $this->getClient()->sendPacket($buffer);
                }
                elseif ($compareAddress->equals($this->getClient()->getAddress())) {
                    $this->getPacket($buffer, $this->getClient());
                    $this->getTargetServer()->sendPacket($buffer);
                }
            }
            unset($this->socketMgr->received[$index]);
            unset($index);
        }
    }


    /**
     * @param string $buffer
     * @param BaseHost $host
     * @return bool
     */
    public function getPacket(string $buffer, BaseHost $host): bool {
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
     * @param InternetAddress $compareAddress
     */
    private function handleRakNetPacket(string $buffer, InternetAddress $compareAddress): void{
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
                    $this->getLogger()->info(TextFormat::AQUA . "MOTD: " . $serverInfo[0]);
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

    private function startThreadWorkers() : void {

        $this->socketMgr->start(PTHREADS_INHERIT_ALL);
        $this->commandMap->consoleCommandReader->start(PTHREADS_INHERIT_ALL);

        // main + socket + commands
        $this->getLogger()->info("§6Started 3 threads!");
    }

    public function stop() : void {
        $this->running = false;
        $this->getLogger()->info("§6Stopping other threads...");

        $this->socketMgr->join();
        $this->socketMgr->notify();
        $this->socketMgr->stop = true;
        unset($this->socketMgr->stop);

        $this->commandMap->consoleCommandReader->join();
        $this->commandMap->consoleCommandReader->notify();
        $this->commandMap->consoleCommandReader->stop = true;
        unset($this->commandMap->consoleCommandReader);
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager() : PluginManager{
        return $this->pluginManager;
    }

    /**
     * @return CommandMap
     */
    public function getCommandMap() : CommandMap{
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
    public function writePacket(string $buffer, string $host, int $port) : void {
         $this->socketMgr->toSend[] = [$buffer, $host, $port];
    }

    /**
     * @return Logger
     */
    public function getLogger() : Logger{
        return $this->logger;
    }

}