<?php

declare(strict_types=1);

namespace acidproxy\command;

use acidproxy\command\base\BoundingBoxCommand;
use acidproxy\command\base\FlyCommand;
use acidproxy\command\base\GamemodeCommand;
use acidproxy\command\base\HelpCommand;
use acidproxy\command\base\PluginsCommand;
use acidproxy\command\base\StopCommand;
use acidproxy\command\base\UnknownCommand;
use acidproxy\command\sender\ConsoleCommandSender;
use acidproxy\ProxyServer;

/**
 * Class CommandMap
 * @package proxy\command
 */
class CommandMap {

    /** @var Command[] $commands */
    private $commands = [];

    /** @var ProxyServer $server */
    private $proxyServer;

    /** @var CommandReader $consoleCommandReader */
    public $consoleCommandReader;

    /** @var ConsoleCommandSender $consoleCommandSender */
    private $consoleCommandSender;

    /**
     * CommandMap constructor.
     * @param ProxyServer $proxy
     */
    public function __construct(ProxyServer $proxy) {
        $this->proxyServer = $proxy;
        $this->consoleCommandSender = new ConsoleCommandSender($this);
        $this->consoleCommandReader = new CommandReader($this);
        $this->registerDefaults();
    }

    public function registerDefaults() {
        $this->registerCommand(new HelpCommand($this));
        $this->registerCommand(new FlyCommand($this));
        $this->registerCommand(new GamemodeCommand($this));
        $this->registerCommand(new PluginsCommand($this));
        $this->registerCommand(new StopCommand($this));
        $this->registerCommand(new BoundingBoxCommand($this));
    }


    /**
     * @return array
     */
    public function getCommands(): array {
        return $this->commands;
    }

    /**
     * @return ProxyServer $proxy
     */
    public function getProxy(): ProxyServer {
        return $this->proxyServer;
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
     * @return Command|null
     */
    public function getCommand(string $name): ?Command {
        if (!isset($this->commands[$name])) {
            return new UnknownCommand($name);
        }
        return $this->commands[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function unregisterCommand(string $name): bool {
        if (!isset($this->commands[$name])) {
            $this->getProxy()->getLogger()->error("Tried to unregister non-existing command");
            return false;
        }
        unset($this->commands[$name]);
        return true;
    }

    /**
     * @param Command $command
     * @return bool
     */
    public function registerCommand(Command $command) {
        if (isset($this->commands[$command->getName()])) {
            $this->getProxy()->getLogger()->error("Tried to register existing command");
            return false;
        }
        $this->commands[$command->getName()] = $command;
        return true;
    }

}