<?php

declare(strict_types=1);

namespace proxy\utils;

use proxy\ProxyServer;
use pocketmine\utils\Terminal;

class Logger {

    /** @var ProxyServer $proxyServer */
    private $proxyServer;

    /**
     * Logger constructor.
     * @param ProxyServer $proxyServer
     */
    public function __construct(ProxyServer $proxyServer) {
        Terminal::init();
        $this->proxyServer = $proxyServer;
    }

    /**
     * @param string $text
     */
    public function info(string $text): void {
        $lines = [];
        foreach (explode(PHP_EOL, $text) as $line) {
            $lines[] = Terminal::toANSI("§b[".gmdate("H:i:s", time())."] §6[Main/Server thread] §eInfo §r> " . $line . "§r");
        }
        echo implode(PHP_EOL, $lines);
        echo PHP_EOL;
    }

    /**
     * @param string $text
     */
    public function error(string $text): void {
        $text = Terminal::toANSI("§b[".gmdate("H:i:s", time())."] §6[Main/Server thread] §4Error §r> " . $text . "§r");
        foreach (explode(PHP_EOL, $text) as $line) {
            echo $line . PHP_EOL;
        }
    }
}