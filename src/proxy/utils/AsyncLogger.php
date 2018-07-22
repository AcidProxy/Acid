<?php

declare(strict_types=1);

namespace proxy\utils;

use pocketmine\utils\Terminal;

/**
 * Class AsyncLogger
 * @package proxy\utils
 */
class AsyncLogger {

    /** @var string $threadName */
    protected $threadName;

    /**
     * AsyncLogger constructor.
     * @param string $threadName
     */
    public function __construct(string $threadName) {
        $this->threadName = $threadName;
    }

    /**
     * @param string $text
     */
    public function info(string $text) {
        echo Terminal::toANSI("§b[".gmdate("H:i:s", time())."] §6[$this->threadName] §eInfo §r> " . $text . "§r" . PHP_EOL);
    }

    /**
     * @param string $text
     */
    public function error(string $text) {
        echo Terminal::toANSI("§b[".gmdate("H:i:s", time())."] §6[$this->threadName] §4Error §r> " . $text . "§r" . PHP_EOL);
    }
}