<?php

declare(strict_types=1);

namespace acidproxy;

use acidproxy\network\AbstractConnection;
use acidproxy\network\DownstreamAbstractConnection;
use acidproxy\network\SocketListener;
use acidproxy\network\UpstreamAbstractConnection;
use acidproxy\command\CommandMap;
use acidproxy\plugin\PluginManager;
use acidproxy\scheduler\Scheduler;
use acidproxy\utils\InternetAddress;
use acidproxy\utils\Logger;
use acidproxy\utils\NetworkUtils;
use pocketmine\utils\Config;
use raklib\protocol\Datagram;
use pocketmine\network\mcpe\protocol\PacketPool;

/**
 * Class ProxyServer
 * @package proxy
 */
class ProxyServer {

    public const PROXY_PLUGIN_API = "1.0.0";

    /** @var ProxyServer $instance */
    private static $instance;

    /** @var bool $running */
    private static $running = true;

    /** @var ProxyUDPSocket $proxyUDPSocket */
    private $proxyUDPSocket;

    /** @var Logger $logger */
    private $logger;

    /** @var SocketListener $socketListener */
    private $socketListener;


    /** @var UpstreamAbstractConnection $upstreamConnection */
    public $upstreamConnection;

    /** @var DownstreamAbstractConnection $downstreamConnection */
    public $downstreamConnection;

    /** @var Scheduler $scheduler */
    private $scheduler;


    /** @var NetworkUtils $networkUtils */
    private $networkUtils;

    /** @var PluginManager $pluginManager */
    private $pluginManager;

    /** @var CommandMap $commandMap */
    private $commandMap;

    /** @var Client $client */
    private $client;


    /**
     * ProxyServer constructor.
     * @param Logger $logger
     * @throws \Exception
     */
    public function __construct(Logger $logger) {
        self::$instance = $this;
        $this->logger = $logger;

        $properties = new Config("proxy.properties", Config::PROPERTIES, [
            "proxy-name" => "AcidProxy v{$this->getVersion()}",
            "bind-ip" => "0.0.0.0",
            "bind-port" => 19132,
            "server-ip" => "pe.gameteam.cz", // gameteam už nefunguje? :(
            "server-port" => 19132
        ]);

        $propertiesData = $properties->getAll();

        $bindIp = $propertiesData["bind-ip"];
        $bindPort = (int)$propertiesData["bind-port"];
        $serverIp = $propertiesData["server-ip"];
        $serverPort = (int)$propertiesData["server-port"];

        $this->proxyUDPSocket = new ProxyUDPSocket();

        try {
            $this->proxyUDPSocket->bind(new InternetAddress($bindIp, $bindPort));
        }
        catch (\Exception $exception) {
            $logger->info("{$exception->getMessage()}, stopping proxy...");
            return;
        }

        $this->client = new Client($this);

        $this->socketListener = new SocketListener($this);
        $this->upstreamConnection = new UpstreamAbstractConnection(new InternetAddress(gethostbyname($serverIp), $serverPort), $this);

        //start reading console commands
        $this->commandMap = new CommandMap($this);
        $this->commandMap->consoleCommandReader->start(PTHREADS_INHERIT_ALL);

        //load plugins
        $this->pluginManager = new PluginManager($this);
        $this->pluginManager->loadPlugins(\acidproxy\PLUGIN_PATH);

        $this->networkUtils = new NetworkUtils($this);
        $this->scheduler = new Scheduler($this);

        cli_set_process_title("AcidProxy v" . \acidproxy\VERSION);

        PacketPool::init();
        $this->tickProcessor();
    }

    /**
     * @return ProxyServer
     */
    public static function getInstance() : ProxyServer{
        return self::$instance;
    }

    /**
     * @return Scheduler
     */
    public function getScheduler(): Scheduler {
        return $this->scheduler;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function shutdown(): void{
        $this->getLogger()->info("Shutting down...");
        $this->proxyUDPSocket->close();
        self::$running = false;
        // TODO: close client
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function tickProcessor(): void {
        while (self::$running !== false) {
            $this->tick();
        }
    }

    /**
     * @return void
     */
    private function tickSchedulers(): void {
        $this->scheduler->tick();
    }

    /**
     * @return void
     */
    private function tick(): void {
        $this->tickNetwork();
        $this->tickCommands();
        $this->tickSchedulers();
    }

    /**
     * @return void
     */
    private function tickCommands(): void {
        $this->commandMap->tick();
    }

    /**
     * @return void
     */
    private function tickNetwork(): void {
        $this->proxyUDPSocket->receive($buffer, $ip, $port);

        foreach ([$buffer, $ip, $port] as $value) {
            if (is_null($value)) return;
        }

        $address = new InternetAddress(gethostbyname($ip), $port);

        if (!$this->upstreamConnection->isConnected()) {
            $this->socketListener->listen($buffer, $address);
        } else {
            if ($address->equals($this->upstreamConnection->address)) {
                $this->networkUtils->writePacket($buffer, $this->downstreamConnection);
                $this->getPacket($buffer, $this->upstreamConnection);
            } elseif ($address->equals($this->downstreamConnection->address)) {
                $pid = ord($buffer{0});
                if (($pid & Datagram::BITFLAG_VALID) !== 0) {
                    if ($pid & Datagram::BITFLAG_ACK || $pid & Datagram::BITFLAG_NAK) {
                        goto a;
                    } else {
                        $datagram = new Datagram($buffer);
                        @$datagram->decode();
                        $datagram->seqNumber = $this->getNetworkUtils()->sendSeqNumber++;
                        $this->networkUtils->writePacket($buffer, $this->upstreamConnection);
                        $this->getPacket($buffer, $this->downstreamConnection);
                    }
                } else {
                    a:
                    $this->networkUtils->writePacket($buffer, $this->upstreamConnection);
                }
            }
        }
    }

    /**
     * @param string $buffer
     * @param AbstractConnection $connection
     */
    public function getPacket(string $buffer, AbstractConnection $connection) : void{
        if(($packet = $this->networkUtils->readDataPacket($buffer)) !== null){
            $connection instanceof UpstreamAbstractConnection ? $this->downstreamConnection->handlePacket($packet) : $this->upstreamConnection->handlePacket($packet);
        }
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager(): PluginManager {
        return $this->pluginManager;
    }

    /**
     * @return CommandMap
     */
    public function getCommandMap(): CommandMap {
        return $this->commandMap;
    }

    /**
     * @return NetworkUtils
     */
    public function getNetworkUtils(): NetworkUtils {
        return $this->networkUtils;
    }

    /**
     * @api
     *
     * @return Logger
     */
    public function getLogger(): Logger {
        return $this->logger;
    }

    /**
     * @return ProxyUDPSocket
     */
    public function getSocket(): ProxyUDPSocket {
        return $this->proxyUDPSocket;
    }


    public function stop() {
        self::$running = false;
        $this->commandMap->consoleCommandReader->stop = true;
    }

    /**
     * @api
     *
     * @return Client
     */
    public function getClient(): Client {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getDataPath(): string {
        return \acidproxy\DATA;
    }

    public function getVersion(): string {
        return \acidproxy\VERSION;
    }

}