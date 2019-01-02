<?php

declare(strict_types=1);

namespace acidproxy\utils;

use pocketmine\utils\Terminal;

/**
 * Class Logger
 * @package proxy\utils
 */
class Logger {

    /**
     * Logger constructor.
     */
    public function __construct() {
        Terminal::init();
    }

    /**
     * @param string $message
     */
    public function info(string $message) {
        echo Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s") . "] " . Terminal::$COLOR_WHITE . "Info > " . Terminal::toANSI($message) . Terminal::$FORMAT_RESET . "\n";
    }

    /**
     * @param string $message
     */
    public function error(string $message) {
        echo Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s") . "] " . Terminal::$COLOR_RED . "Error > " . Terminal::toANSI($message) . Terminal::$FORMAT_RESET . "\n";
    }
}