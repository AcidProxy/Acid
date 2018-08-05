<?php

declare(strict_types=1);

namespace proxy\command\sender;

use proxy\command\CommandMap;
use proxy\utils\Logger;

/**
 * Class ConsoleCommandSender
 * @package proxy\command\sender
 */
class ConsoleCommandSender implements Sender
{

    /** @var CommandMap $commandMap */
    private $commandMap;

    /**
     * ConsoleCommandSender constructor.
     * @param CommandMap $commandMap
     */
    public function __construct(CommandMap $commandMap)
    {
        $this->commandMap = $commandMap;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message)
    {
        Logger::log($message);
    }

    /**
     * @return CommandMap $commandMap
     */
    public function getCommandMap()
    {
        return $this->commandMap;
    }
}