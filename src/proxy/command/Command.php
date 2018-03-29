<?php

namespace proxy\command;


use proxy\hosts\Client;
use proxy\hosts\ProxyClient;

abstract class Command
{
    /**
     * @var string $usageMessage
     */
    private $usageMessage;

    /**
     * @var string $name
     */
    private $name;

    private $aliases = [];

    /**
     * Command constructor.
     * @param string $name
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $usageMessage = null, array $aliases = [])
    {
        $this->name = $name;
        $this->usageMessage = is_null($this->usageMessage) ? "" : $this->usageMessage;
        $this->aliases = $aliases;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUsage() : string{
        return $this->usageMessage;
    }

    /**
     * @param string $usageMessage
     */
    public function setUsage(string $usageMessage){
        $this->usageMessage = $usageMessage;
    }

    /**
     * @param ProxyClient $sender
     * @param array $args
     * @return bool
     */
    abstract public function execute(ProxyClient $sender, array $args) : bool;

}