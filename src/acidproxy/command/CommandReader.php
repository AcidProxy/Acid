<?php

declare(strict_types=1);

namespace acidproxy\command;

/**
 * Class CommandReader
 * @package proxy\command
 */
class CommandReader extends \Thread {

    /** @var CommandMap $commandMap */
    public $commandMap;

    /** @var bool $stop */
    public $stop = false;

    /** @var \Threaded $buffer */
    public $buffer;

    /**
     * CommandReader constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        $this->commandMap = $commandMap;
        $this->buffer = new \Threaded;
    }

    public function run() {
        $resource = fopen("php://stdin", "r");
        while ($this->stop !== true) {
            $commandLine = trim(fgets($resource));
            if ($commandLine != "") {
                $this->buffer[] = $commandLine;
            }
        }
    }

    /**
     * @return CommandMap $commandMap
     */
    public function getCommandMap(): CommandMap {
        return $this->commandMap;
    }
}