<?php namespace proxy\utils;

use pocketmine\utils\TextFormat;
use proxy\ProxyServer;
use pocketmine\utils\Terminal;

class Logger
{

    /**
     * @var ProxyServer
     */
    private $proxyServer;

    /**
     * Logger constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer)
    {
        Terminal::init();
        $this->proxyServer = $proxyServer;
    }

    /**
     * @param string $text
     */
    public function info(string $text) : void{
            echo Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s") . "] " . Terminal::toANSI($text) . PHP_EOL;
    }

    /**
     * @param string $text
     */
    public function error(string $text) : void{
        echo Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s") . "] " . Terminal::$COLOR_DARK_RED . "[ERROR] " . $text . PHP_EOL;
    }

}