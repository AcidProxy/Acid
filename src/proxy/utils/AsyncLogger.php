<?php

declare(strict_types=1);

namespace proxy\utils;

use pocketmine\utils\Terminal;

/**
 * Class AsyncLogger
 * @package proxy\utils
 */
class AsyncLogger
{

    private const PREFIX_INFO = 0;
    private const PREFIX_ERROR = 1;
    private const PREFIX_WARNING = 2;
    private const PREFIX_DEBUG = 3;

    /** @var string $threadName */
    protected $threadName;

    /**
     * AsyncLogger constructor.
     * @param string $threadName
     */
    public function __construct(string $threadName)
    {
        $this->threadName = $threadName;
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function info(string $text): void
    {
        foreach (explode(PHP_EOL, $text) as $line) {
            if ($line != "") {
                echo $this->getPrefix(self::PREFIX_INFO) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function error(string $text): void
    {
        foreach (explode(PHP_EOL, $text) as $line) {
            if ($line != "") {
                echo $this->getPrefix(self::PREFIX_ERROR) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function warning(string $text): void
    {
        foreach (explode(PHP_EOL, $text) as $line) {
            if ($line != "") {
                echo $this->getPrefix(self::PREFIX_WARNING) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    public function debug(string $text): void
    {
        foreach (explode(PHP_EOL, $text) as $line) {
            if ($line != "") {
                echo $this->getPrefix(self::PREFIX_DEBUG) . Terminal::toANSI($line . "§r") . PHP_EOL;
            }
        }
    }

    /**
     * @param int $prefix
     *
     * @return string $prefix
     */
    private function getPrefix(int $prefix): string
    {
        switch ($prefix) {
            case self::PREFIX_INFO:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[$this->threadName] " . Terminal::$COLOR_YELLOW . "Info" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_ERROR:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[$this->threadName] " . Terminal::$COLOR_RED . "Error" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_WARNING:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[$this->threadName] " . Terminal::$COLOR_LIGHT_PURPLE . "Warning" . Terminal::$FORMAT_RESET . " > ";
            case self::PREFIX_DEBUG:
                return Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::$COLOR_GOLD . "[$this->threadName] " . Terminal::$COLOR_GREEN . "Debug" . Terminal::$FORMAT_RESET . " > ";
        }
        return "";
    }
}