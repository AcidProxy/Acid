<?php

declare(strict_types=1);

namespace proxy\command;

use proxy\command\base\UnknownCommand;
use proxy\command\sender\ConsoleCommandSender;
use proxy\ProxyServer;

/**
 * Class CommandMap
 * @package proxy\command
 */
class CommandMap {

    /** @var Command[] $commands */
    private $commands = [];

    /** @var ProxyServer $server */
    private $server;

    /** @var CommandReader $consoleCommandReader */
    public $consoleCommandReader;

    /** @var ConsoleCommandSender $consoleCommandSender */
    private $consoleCommandSender;

    /**
     * CommandMap constructor.
     * @param ProxyServer $server
     */
    public function __construct(ProxyServer $server) {
        $this->server = $server;
        $this->consoleCommandSender = new ConsoleCommandSender($this);
        $this->consoleCommandReader = new CommandReader($this);
    }

    /**
     * @return array
     */
    public function getCommands() : array{
        return $this->commands;
    }

    /**
     * @return ProxyServer $server
     */
    public function getServer() : ProxyServer{
        return $this->server;
    }

    public function tick() {
        foreach ($this->consoleCommandReader->buffer as $index => $commandLine) {
            $args = explode(" ", $commandLine);
            $name = $args[0];
            array_shift($args);

            $this->getCommand($name)->execute($this->consoleCommandSender, $args);

            unset($this->consoleCommandReader->buffer[$index]);
        }
    }

    /**
     * @param string $name
     * @return null|\proxy\command\Command
     */
    public function getCommand(string $name) : ?Command{
        if(!isset($this->commands[$name])){
            return new UnknownCommand($name);
        }
        return $this->commands[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function unregisterCommand(string $name) : bool{
        if(!isset($this->commands[$name])){
            $this->getServer()->getLogger()->error("Tried to unregister non-existing command");
            return false;
        }
        unset($this->commands[$name]);
        return true;
    }

}