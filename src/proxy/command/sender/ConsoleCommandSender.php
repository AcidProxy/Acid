<?php

declare(strict_types=1);

namespace proxy\command\sender;
use proxy\command\CommandMap;

/**
 * Class ConsoleCommandSender
 * @package proxy\command\sender
 */
class ConsoleCommandSender implements Sender {

    /** @var CommandMap $commandMap */
    private $commandMap;

    /**
     * ConsoleCommandSender constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap) {
        $this->commandMap = $commandMap;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message) {
        $this->getCommandMap()->getProxy()->getLogger()->info($message);
    }

    /**
     * @return CommandMap $commandMap
     */
    public function getCommandMap() {
        return $this->commandMap;
    }
}