<?php

declare(strict_types=1);

namespace proxy\utils;

use pocketmine\utils\Terminal;

/**
 * Class Logger
 * @package proxy\utils
 */
class Logger
{

    /**
     * @param string $message
     */
    public static function log(string $message) : void{
        echo Terminal::$COLOR_AQUA . "[" . gmdate("H:i:s", time()) . "] " . Terminal::toANSI($message) . PHP_EOL;
    }


}