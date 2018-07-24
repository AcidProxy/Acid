<?php

declare(strict_types=1);

namespace proxy\utils;

use proxy\ProxyServer;
use pocketmine\utils\Terminal;

/**
 * Class Logger
 * @package proxy\utils
 */
class Logger {

    private const PREFIX_INFO = 0;
    private const PREFIX_ERROR = 1;
    private const PREFIX_WARNING = 2;
    private const PREFIX_DEBUG = 3;

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
     *
     * @return void
     */
    public function info(string $text): void {
        foreach (explode(PHP_EOL, $text) as $line) {
            if($line != "") {
                echo $this->getPrefix(self::PREFIX_INFO) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function error(string $text): void {
        foreach (explode(PHP_EOL, $text) as $line) {
            if($line != "") {
                echo $this->getPrefix(self::PREFIX_ERROR) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function warning(string $text): void {
        foreach (explode(PHP_EOL, $text) as $line) {
            if($line != "") {
                echo $this->getPrefix(self::PREFIX_WARNING) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function debug(string $text): void {
        foreach (explode(PHP_EOL, $text) as $line) {
            if($line != "") {
                echo $this->getPrefix(self::PREFIX_DEBUG) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param int $prefix
     *
     * @return string $prefix
     */
    private function getPrefix(int $prefix): string {
        switch ($prefix) {
            case self::PREFIX_INFO:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[Main/Server thread] " . Terminal::$COLOR_YELLOW . "Info" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_ERROR:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[Main/Server thread] " . Terminal::$COLOR_RED . "Error" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_WARNING:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[Main/Server thread] " . Terminal::$COLOR_LIGHT_PURPLE . "Warning" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_DEBUG:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[Main/Server thread] " . Terminal::$COLOR_GREEN . "Debug" . Terminal::$FORMAT_RESET . " > ";
        }
        return "";
    }
}