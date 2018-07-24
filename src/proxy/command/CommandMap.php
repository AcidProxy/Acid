<?php

declare(strict_types=1);

namespace proxy\command;

use proxy\command\base\FlyCommand;
use proxy\command\base\GamemodeCommand;
use proxy\command\base\HelpCommand;
use proxy\command\base\PluginsCommand;
use proxy\command\base\StopCommand;
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
        $this->registerBase();
    }

    public function registerBase() {
        $this->registerCommand(new GamemodeCommand($this));
        $this->registerCommand(new HelpCommand($this));
        $this->registerCommand(new PluginsCommand($this));
        $this->registerCommand(new StopCommand($this));
        $this->registerCommand(new FlyCommand($this));
    }

    /**
     * @return array
     */
    public function getCommands() : array{
        return $this->commands;
    }

    /**
     * @return ProxyServer $proxy
     */
    public function getProxy(): ProxyServer{
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
    public function registerCommand(Command $command){
        if(isset($this->commands[$command->getName()])){
            $this->getProxy()->getLogger()->error("Tried to register existing command");
            return false;
        }
        $this->commands[$command->getName()] = $command;
        return true;
    }

}