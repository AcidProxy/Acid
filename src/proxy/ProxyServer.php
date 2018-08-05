<?php

declare(strict_types=1);

namespace proxy;

use pocketmine\network\mcpe\protocol\TextPacket;
use proxy\command\base\FlyCommand;
use proxy\command\base\GamemodeCommand;
use proxy\command\base\HelpCommand;
use proxy\command\base\PluginsCommand;
use proxy\command\base\StopCommand;
use proxy\network\AbstractConnection;
use proxy\network\DownstreamAbstractConnection;
use proxy\network\Socket;
use proxy\network\SocketListener;
use proxy\network\UpstreamAbstractConnection;
use proxy\networkOld\socket\SocketManager;
use proxy\networkOld\raknet\client\SessionManager;
use proxy\command\CommandMap;
use proxy\hosts\BaseHost;
use proxy\hosts\ProxyClient;
use proxy\hosts\TargetServer;
use proxy\plugin\PluginManager;
use proxy\scheduler\Task;
use proxy\scheduler\TaskScheduler;
use proxy\utils\Logger;
use proxy\utils\NetworkUtils;
//raklib
use raklib\protocol\Datagram;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;
use raklib\server\UDPServerSocket;
use raklib\utils\InternetAddress;
//pocketmine
use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\TextFormat;

/**
 * Class ProxyServer
 * @package proxy
 */
class ProxyServer
{

    /**
     * @var ProxyServer $instance
     */
    private static $instance;

    public const PLUGINS_DIR = "plugins";
    public const SERVER_API = "1.0.0";

    /** @var bool $running */
    protected $running = true;

    /**
     * @var Socket $serverSocket;
     */
    private $serverSocket;

    /**
     * @var SocketListener $socketListener
     */
    private $socketListener;

    /**
     * @var UpstreamAbstractConnection $upstreamConnection
     */
    public $upstreamConnection;

    /**
     * @var DownstreamAbstractConnection $downstreamConnection
     */
    public $downstreamConnection;

    /** @var NetworkUtils $networkUtils */
    private $networkUtils;

    /** @var SessionManager $sessionManager */
    private $sessionManager;

    /** @var PluginManager $pluginManager */
    private $pluginManager;

    /** @var CommandMap $commandMap */
    private $commandMap;

    /**
     * @var TaskScheduler $taskScheduler
     */
    private $taskScheduler;

    /**
     * @var int $lastTick
     */
    private $lastTick = 0;


    /**
     * ProxyServer constructor.
     * @param string $serverAddress
     * @param int $serverPort
     * @param int $bindPort
     * @param string $bindAddress
     * @throws \Exception
     */
    public function __construct(string $serverAddress, int $serverPort, int $bindPort, string $bindAddress = '0.0.0.0')
    {
        define('acid\START_TIME', microtime(true));
        self::$instance = $this;

        Logger::log("Starting Acid v" . self::SERVER_API);
        cli_set_process_title("Acid v" . self::SERVER_API);

        //register default commands
        $this->commandMap = new CommandMap($this);
        $commands = [new GamemodeCommand($this->commandMap), new HelpCommand($this->commandMap), new PluginsCommand($this->commandMap), new StopCommand($this->commandMap), new FlyCommand($this->commandMap)];
        for($i=0; $i < count($commands); ++$i){
            $this->commandMap->registerCommand($commands[$i]);
        }

        //load plugins
        $this->pluginManager = new PluginManager($this);
        $this->pluginManager->loadPlugins(self::PLUGINS_DIR);

        //downstream/upstream connection & udp socket
        $this->serverSocket = new Socket(100);
        $this->serverSocket->bind(new InternetAddress($bindAddress, $bindPort, 4));
        $this->serverSocket->start();

        $this->socketListener = new SocketListener($this);

        $this->upstreamConnection = new UpstreamAbstractConnection(new InternetAddress(gethostbyname($serverAddress), $serverPort, 4), $this);

        //start reading console commands
        $this->commandMap->consoleCommandReader->start(PTHREADS_INHERIT_ALL);

        $this->networkUtils = new NetworkUtils($this);
        $this->taskScheduler = new TaskScheduler();


        PacketPool::init();
        $this->tickProcessor();
    }

    public static function getInstance() : ProxyServer{
        return self::$instance;
    }

    /**
     * @return TaskScheduler
     */
    public function getScheduler() : TaskScheduler{
        return $this->taskScheduler;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function shutdown() : void{
        Logger::log("Shutting down...");

        $this->serverSocket->close();
        $this->running = false;
        //todo: disconnect the client
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function tickProcessor(): void
    {
        while ($this->running !== false) {
            $this->tick();
        }
    }

    /**
     * @return void
     */
    private function tickSchedulers() : void{
        if(microtime(true) - \acid\START_TIME >= $this->lastTick / 20) {
            foreach($this->taskScheduler->getTaskList() as $id => $task){
                if($this->lastTick %$task->getPeriod() === 0){
                    $task->run();
                }
            }
            $this->lastTick++;
        }
    }

    /**
     * @return void
     */
    private function tick(): void
    {
        $this->tickNetwork();
        $this->tickCommands();
        $this->tickSchedulers();
    }

    /**
     * @return void
     */
    private function tickCommands(): void
    {
        $this->commandMap->tick();
    }

    /**
     * @return void
     */
    private function tickNetwork(): void
    {
        foreach ($this->serverSocket->bufferQueue as $index => $data) {
            /** @var string $buffer */
            $buffer = $data[0];
            /** @var string $address */
            $address = $data[1];
            /** @var int $port */
            $port = $data[2];


            foreach ([$buffer, $address, $port] as $value) {
                if (is_null($value)) return;
            }

            unset($this->serverSocket->bufferQueue[$index]);
            $compareAddress = new InternetAddress(gethostbyname($address), $port, 4);
            if (!$this->upstreamConnection->isConnected()) {
                $this->socketListener->listen($buffer, $compareAddress);
            } else {
                if ($compareAddress->equals($this->upstreamConnection->address)) {
                    $this->networkUtils->writePacket($buffer, $this->downstreamConnection);
                    $this->getPacket($buffer, $this->upstreamConnection);
                } elseif ($compareAddress->equals($this->downstreamConnection->address)) {
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
     * @return Socket
     */
    public function getSocket() : Socket{
        return $this->serverSocket;
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * @return CommandMap
     */
    public function getCommandMap(): CommandMap
    {
        return $this->commandMap;
    }

    /**
     * @return NetworkUtils
     */
    public function getNetworkUtils(): NetworkUtils
    {
        return $this->networkUtils;
    }

}